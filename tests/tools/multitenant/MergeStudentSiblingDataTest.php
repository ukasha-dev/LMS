<?php

use PHPUnit\Framework\TestCase;

final class MergeStudentSiblingDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_sibling_test_source');
        $admin->exec('CREATE DATABASE merge_sibling_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_sibling_test_target');
        $admin->exec('CREATE DATABASE merge_sibling_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_sibling_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_sibling_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $studentSchema = "id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL, is_active VARCHAR(10) NOT NULL DEFAULT 'yes'";
        $siblingSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, sibling_id INT NOT NULL';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE students ({$studentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_sibling ({$siblingSchema}{$tenantCol})");
        }
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_sibling_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_sibling_test_target');
    }

    public function testMergesSiblingLinksWithBothSidesResolved(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001'), (102, 'ADM-002')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25), (2, 'ADM-002', 25)");
        $this->source->exec('INSERT INTO student_sibling (id, student_id, sibling_id) VALUES (901, 101, 102)');

        $merger = new MergeStudentSiblingData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['student_sibling_migrated']);
        $this->assertSame(0, $result['student_sibling_skipped']);

        $row = $this->target->query('SELECT * FROM student_sibling')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int) $row['student_id']);
        $this->assertSame(2, (int) $row['sibling_id']);
        $this->assertSame(25, (int) $row['tenant_id']);
    }

    public function testSkipsLinkWhenEitherSideIsUnresolvable(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        // sibling_id=102 has no matching target row -- must skip, not
        // insert a link that half-references a nonexistent student.
        $this->source->exec('INSERT INTO student_sibling (id, student_id, sibling_id) VALUES (901, 101, 102)');

        $merger = new MergeStudentSiblingData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(0, $result['student_sibling_migrated']);
        $this->assertSame(1, $result['student_sibling_skipped']);
    }

    public function testRefusesToRunAgainIfTenantAlreadyHasStudentSiblingRows(): void
    {
        $this->target->exec('INSERT INTO student_sibling (id, student_id, sibling_id, tenant_id) VALUES (1, 1, 2, 25)');
        $this->source->exec('INSERT INTO student_sibling (id, student_id, sibling_id) VALUES (901, 101, 102)');

        $merger = new MergeStudentSiblingData($this->source, $this->target, 25);

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected run() to refuse when the tenant already has student_sibling rows');
    }
}
