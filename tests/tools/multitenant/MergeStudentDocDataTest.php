<?php

use PHPUnit\Framework\TestCase;

final class MergeStudentDocDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_studentdoc_test_source');
        $admin->exec('CREATE DATABASE merge_studentdoc_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_studentdoc_test_target');
        $admin->exec('CREATE DATABASE merge_studentdoc_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_studentdoc_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_studentdoc_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $studentSchema = "id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL, is_active VARCHAR(10) NOT NULL DEFAULT 'yes'";
        $docSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT DEFAULT NULL, title VARCHAR(200) DEFAULT NULL,'
            . ' doc VARCHAR(200) DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE students ({$studentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_doc ({$docSchema}{$tenantCol})");
        }
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_studentdoc_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_studentdoc_test_target');
    }

    public function testMergesDocumentsWithResolvedStudentId(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->source->exec("INSERT INTO student_doc (id, student_id, title, doc) VALUES (901, 101, 'Birth Certificate', 'birth_cert.pdf')");

        $merger = new MergeStudentDocData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['student_doc_migrated']);
        $this->assertSame(0, $result['student_doc_skipped']);

        $row = $this->target->query('SELECT * FROM student_doc')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int) $row['student_id']);
        $this->assertSame('Birth Certificate', $row['title']);
        $this->assertSame(25, (int) $row['tenant_id']);
    }

    public function testSkipsDocumentsReferencingAnUnresolvableStudent(): void
    {
        $this->source->exec("INSERT INTO student_doc (id, student_id, title) VALUES (901, 999, 'Orphan Doc')");

        $merger = new MergeStudentDocData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(0, $result['student_doc_migrated']);
        $this->assertSame(1, $result['student_doc_skipped']);
        $this->assertSame(1, $result['student_doc_source_total']);
    }

    public function testRefusesToRunAgainIfTenantAlreadyHasStudentDocRows(): void
    {
        $this->target->exec("INSERT INTO student_doc (id, student_id, tenant_id) VALUES (1, 1, 25)");
        $this->source->exec("INSERT INTO student_doc (id, student_id, title) VALUES (901, 101, 'Existing')");

        $merger = new MergeStudentDocData($this->source, $this->target, 25);

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected run() to refuse when the tenant already has student_doc rows');
    }
}
