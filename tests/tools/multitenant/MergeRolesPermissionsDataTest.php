<?php

use PHPUnit\Framework\TestCase;

final class MergeRolesPermissionsDataTest extends TestCase
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

        $rolesSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) DEFAULT NULL';
        $this->source->exec("CREATE TABLE roles ({$rolesSchema})");
        $this->target->exec("CREATE TABLE roles ({$rolesSchema}, tenant_id INT NOT NULL)");

        $catSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, short_code VARCHAR(100) DEFAULT NULL';
        $this->source->exec("CREATE TABLE permission_category ({$catSchema})");
        $this->target->exec("CREATE TABLE permission_category ({$catSchema}, tenant_id INT NOT NULL)");

        $rpSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, role_id INT DEFAULT NULL, perm_cat_id INT DEFAULT NULL,'
            . ' can_view INT DEFAULT NULL, can_add INT DEFAULT NULL, can_edit INT DEFAULT NULL, can_delete INT DEFAULT NULL';
        $this->source->exec("CREATE TABLE roles_permissions ({$rpSchema})");
        $this->target->exec("CREATE TABLE roles_permissions ({$rpSchema}, tenant_id INT NOT NULL)");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_target');
    }

    public function testResolvesRoleAndCategoryByNaturalKeyEvenWhenSourceAndTargetIdsDiffer(): void
    {
        // Source and target ids deliberately don't line up (mirrors the real
        // al_hafeez_campus shape discovered live: role ids are NOT 1:1
        // between source and target once a row has ever been deleted).
        $this->source->exec("INSERT INTO roles (id, name) VALUES (9, 'Admin')");
        $this->target->exec("INSERT INTO roles (id, name, tenant_id) VALUES (1, 'Admin', 25)");

        $this->source->exec("INSERT INTO permission_category (id, short_code) VALUES (7, 'fees_collection')");
        $this->target->exec("INSERT INTO permission_category (id, short_code, tenant_id) VALUES (3, 'fees_collection', 25)");

        $this->source->exec(
            'INSERT INTO roles_permissions (id, role_id, perm_cat_id, can_view, can_add, can_edit, can_delete) '
            . 'VALUES (1, 9, 7, 1, 1, 0, 0)'
        );

        $merger = new MergeRolesPermissionsData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['roles_permissions_migrated']);
        $this->assertSame(0, $result['roles_permissions_skipped']);

        $row = $this->target->query('SELECT * FROM roles_permissions')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int) $row['role_id'], 'must resolve to the TARGET role id (1), not the source id (9)');
        $this->assertSame(3, (int) $row['perm_cat_id'], 'must resolve to the TARGET category id (3), not the source id (7)');
        $this->assertSame(25, (int) $row['tenant_id']);
    }

    public function testSkipsRowsWhoseRoleOrCategoryCannotBeResolved(): void
    {
        $this->source->exec("INSERT INTO roles (id, name) VALUES (9, 'Admin')");
        // No matching target role for 'Admin' -- unresolvable.
        $this->source->exec("INSERT INTO permission_category (id, short_code) VALUES (7, 'fees_collection')");
        $this->target->exec("INSERT INTO permission_category (id, short_code, tenant_id) VALUES (3, 'fees_collection', 25)");

        $this->source->exec(
            'INSERT INTO roles_permissions (id, role_id, perm_cat_id, can_view) VALUES (1, 9, 7, 1)'
        );

        $merger = new MergeRolesPermissionsData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(0, $result['roles_permissions_migrated']);
        $this->assertSame(1, $result['roles_permissions_skipped']);

        $count = (int) $this->target->query('SELECT COUNT(*) AS c FROM roles_permissions')->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(0, $count);
    }

    public function testRefusesToRunAgainIfTenantAlreadyHasRolesPermissionsRows(): void
    {
        $this->target->exec("INSERT INTO roles_permissions (id, can_view, tenant_id) VALUES (1, 1, 25)");
        $this->source->exec("INSERT INTO roles_permissions (id, role_id, perm_cat_id, can_view) VALUES (1, 9, 7, 1)");

        $merger = new MergeRolesPermissionsData($this->source, $this->target, 25);

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected run() to refuse when tenant 25 already has roles_permissions rows');

        $count = (int) $this->target->query('SELECT COUNT(*) AS c FROM roles_permissions WHERE tenant_id = 25')->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(1, $count, 'Refusing to run must not insert any new rows');
    }
}
