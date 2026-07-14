<?php

use PHPUnit\Framework\TestCase;

final class MergeGradeDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_test_source');
        $admin->exec('CREATE DATABASE merge_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_target');
        $admin->exec('CREATE DATABASE merge_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $schema = 'id INT AUTO_INCREMENT PRIMARY KEY, exam_type VARCHAR(250) DEFAULT NULL,'
            . ' name VARCHAR(100) DEFAULT NULL, point FLOAT(10,1) DEFAULT NULL,'
            . ' mark_from FLOAT(10,2) DEFAULT NULL, mark_upto FLOAT(10,2) DEFAULT NULL,'
            . " description TEXT DEFAULT NULL, is_active VARCHAR(255) DEFAULT 'no'";
        $this->source->exec("CREATE TABLE grades ({$schema})");
        $this->target->exec("CREATE TABLE grades ({$schema}, tenant_id INT NOT NULL)");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_target');
    }

    public function testMergesGradesForTenant(): void
    {
        $this->source->exec("INSERT INTO grades (id, exam_type, name, point, mark_from, mark_upto) VALUES (1, 'Terminal', 'A+', 4.0, 90, 100)");

        $merger = new MergeGradeData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['grades_migrated']);

        $grade = $this->target->query('SELECT * FROM grades')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('A+', $grade['name']);
        $this->assertSame(25, (int) $grade['tenant_id']);
    }

    public function testRefusesToRunAgainIfTenantAlreadyHasGradeRows(): void
    {
        $this->target->exec("INSERT INTO grades (id, name, tenant_id) VALUES (1, 'Existing', 25)");
        $this->source->exec("INSERT INTO grades (id, name) VALUES (1, 'A+')");

        $merger = new MergeGradeData($this->source, $this->target, 25);

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected run() to refuse when tenant 25 already has grade rows');

        $count = (int) $this->target->query('SELECT COUNT(*) AS c FROM grades WHERE tenant_id = 25')->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(1, $count, 'Refusing to run must not insert any new rows');
    }
}
