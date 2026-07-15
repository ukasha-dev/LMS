<?php

use PHPUnit\Framework\TestCase;

final class MergePermissionGroupDataTest extends TestCase
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

        $schema = 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) DEFAULT NULL,'
            . " short_code VARCHAR(100) NOT NULL, is_active INT DEFAULT 0, system INT NOT NULL DEFAULT 0";
        $this->source->exec("CREATE TABLE permission_group ({$schema})");
        $this->target->exec("CREATE TABLE permission_group ({$schema})");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_target');
    }

    public function testMergesPermissionGroupsWithSourceIdsPreserved(): void
    {
        $this->source->exec("INSERT INTO permission_group (id, name, short_code, system) VALUES (5, 'Fees', 'fees', 0)");

        $merger = new MergePermissionGroupData($this->source, $this->target);
        $result = $merger->run();

        $this->assertSame(1, $result['permission_group_migrated']);

        $row = $this->target->query('SELECT * FROM permission_group')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(5, (int) $row['id']);
        $this->assertSame('Fees', $row['name']);
    }

    public function testRefusesToRunAgainIfTargetAlreadyHasRows(): void
    {
        $this->target->exec("INSERT INTO permission_group (id, name, short_code, system) VALUES (1, 'Existing', 'existing', 0)");
        $this->source->exec("INSERT INTO permission_group (id, name, short_code, system) VALUES (1, 'Fees', 'fees', 0)");

        $merger = new MergePermissionGroupData($this->source, $this->target);

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected run() to refuse when the global table already has rows');

        $count = (int) $this->target->query('SELECT COUNT(*) AS c FROM permission_group')->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(1, $count, 'Refusing to run must not insert any new rows');
    }
}
