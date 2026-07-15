<?php

use PHPUnit\Framework\TestCase;

final class MergePermissionCategoryDataTest extends TestCase
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

        $schema = 'id INT AUTO_INCREMENT PRIMARY KEY, perm_group_id INT DEFAULT NULL,'
            . ' name VARCHAR(100) DEFAULT NULL, short_code VARCHAR(100) DEFAULT NULL,'
            . ' enable_view INT DEFAULT 0, enable_add INT DEFAULT 0, enable_edit INT DEFAULT 0, enable_delete INT DEFAULT 0';
        $this->source->exec("CREATE TABLE permission_category ({$schema})");
        $this->target->exec("CREATE TABLE permission_category ({$schema}, tenant_id INT NOT NULL)");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_target');
    }

    public function testMergesPermissionCategoriesForTenantPreservingGlobalGroupReference(): void
    {
        $this->source->exec("INSERT INTO permission_category (id, perm_group_id, name, short_code) VALUES (1, 5, 'Student', 'student')");

        $merger = new MergePermissionCategoryData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['permission_category_migrated']);

        $row = $this->target->query('SELECT * FROM permission_category')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Student', $row['name']);
        $this->assertSame(5, (int) $row['perm_group_id'], 'perm_group_id references the global table and must pass through unremapped');
        $this->assertSame(25, (int) $row['tenant_id']);
    }

    public function testRefusesToRunAgainIfTenantAlreadyHasCategoryRows(): void
    {
        $this->target->exec("INSERT INTO permission_category (id, name, short_code, tenant_id) VALUES (1, 'Existing', 'existing', 25)");
        $this->source->exec("INSERT INTO permission_category (id, perm_group_id, name, short_code) VALUES (1, 5, 'Student', 'student')");

        $merger = new MergePermissionCategoryData($this->source, $this->target, 25);

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected run() to refuse when tenant 25 already has category rows');

        $count = (int) $this->target->query('SELECT COUNT(*) AS c FROM permission_category WHERE tenant_id = 25')->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(1, $count, 'Refusing to run must not insert any new rows');
    }
}
