<?php

use PHPUnit\Framework\TestCase;

final class MergeGlobalReferenceDataTest extends TestCase
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

        $schema = 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(250) DEFAULT NULL, status INT DEFAULT NULL';
        $this->source->exec("CREATE TABLE online_admission_fields ({$schema})");
        $this->target->exec("CREATE TABLE online_admission_fields ({$schema})");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_target');
    }

    public function testCopiesAllColumnsWithSourceIdsPreserved(): void
    {
        $this->source->exec("INSERT INTO online_admission_fields (id, name, status) VALUES (7, 'Blood Group', 1)");

        $merger = new MergeGlobalReferenceData($this->source, $this->target, 'online_admission_fields');
        $result = $merger->run();

        $this->assertSame(1, $result['migrated']);

        $row = $this->target->query('SELECT * FROM online_admission_fields')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(7, (int) $row['id']);
        $this->assertSame('Blood Group', $row['name']);
        $this->assertSame(1, (int) $row['status']);
    }

    public function testRefusesToRunAgainIfTargetAlreadyHasRows(): void
    {
        $this->target->exec("INSERT INTO online_admission_fields (id, name) VALUES (1, 'Existing')");
        $this->source->exec("INSERT INTO online_admission_fields (id, name) VALUES (1, 'Blood Group')");

        $merger = new MergeGlobalReferenceData($this->source, $this->target, 'online_admission_fields');

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected run() to refuse when the global table already has rows');

        $count = (int) $this->target->query('SELECT COUNT(*) AS c FROM online_admission_fields')->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(1, $count, 'Refusing to run must not insert any new rows');
    }
}
