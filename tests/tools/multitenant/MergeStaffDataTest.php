<?php

use PHPUnit\Framework\TestCase;

final class MergeStaffDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_staff_test_source');
        $admin->exec('CREATE DATABASE merge_staff_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_staff_test_target');
        $admin->exec('CREATE DATABASE merge_staff_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_staff_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_staff_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Explicit column lists matching MergeStaffData::run()'s whitelist SELECTs,
        // which in turn match school_saas's real schema for these tables (see
        // sql/multitenant/002_add_staff_tables.sql from Task 1) — not just the
        // handful of columns the assertions happen to check.
        $staffSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, employee_id VARCHAR(200) NOT NULL,'
            . ' name VARCHAR(200) NOT NULL, surname VARCHAR(200) DEFAULT NULL,'
            . ' email VARCHAR(200) NOT NULL, password VARCHAR(250) NOT NULL,'
            . ' gender VARCHAR(50) DEFAULT NULL, image VARCHAR(200) DEFAULT NULL,'
            . ' is_active INT NOT NULL DEFAULT 1, verification_code VARCHAR(100) DEFAULT NULL,'
            . ' lang_id INT NOT NULL DEFAULT 0, currency_id INT NOT NULL DEFAULT 0,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE staff ({$staffSchema})");
        $this->target->exec("CREATE TABLE staff ({$staffSchema}, tenant_id INT NOT NULL)");

        $rolesSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) DEFAULT NULL,'
            . ' slug VARCHAR(150) DEFAULT NULL, is_active INT NOT NULL DEFAULT 1,'
            . ' is_system INT NOT NULL DEFAULT 0, is_superadmin INT NOT NULL DEFAULT 0,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE roles ({$rolesSchema})");
        $this->target->exec("CREATE TABLE roles ({$rolesSchema}, tenant_id INT NOT NULL)");

        $staffRolesSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT NOT NULL, role_id INT NOT NULL,'
            . ' is_active INT NOT NULL DEFAULT 1,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE staff_roles ({$staffRolesSchema})");
        $this->target->exec("CREATE TABLE staff_roles ({$staffRolesSchema}, tenant_id INT NOT NULL)");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_staff_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_staff_test_target');
    }

    public function testMergesStaffRolesAndStaffRolesWithRemappedForeignKeys(): void
    {
        $this->source->exec("INSERT INTO staff (id, employee_id, name, email, password) VALUES (1, 'EMP-1', 'Alice', 'alice@example.com', 'hash1')");
        $this->source->exec("INSERT INTO roles (id, name) VALUES (1, 'Coordinator')");
        $this->source->exec('INSERT INTO staff_roles (id, staff_id, role_id) VALUES (1, 1, 1)');

        $merger = new MergeStaffData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['staff_migrated']);
        $this->assertSame(1, $result['roles_migrated']);
        $this->assertSame(1, $result['staff_roles_migrated']);

        $staff = $this->target->query('SELECT * FROM staff')->fetch(PDO::FETCH_ASSOC);
        $role = $this->target->query('SELECT * FROM roles')->fetch(PDO::FETCH_ASSOC);
        $staffRole = $this->target->query('SELECT * FROM staff_roles')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('Alice', $staff['name']);
        $this->assertSame(25, (int) $staff['tenant_id']);
        $this->assertSame(25, (int) $role['tenant_id']);
        $this->assertSame(25, (int) $staffRole['tenant_id']);
        $this->assertSame((int) $staff['id'], (int) $staffRole['staff_id']);
        $this->assertSame((int) $role['id'], (int) $staffRole['role_id']);
    }

    public function testStartsStaffAndRoleIdsAfterExistingTargetRowsToAvoidCollision(): void
    {
        $this->target->exec("INSERT INTO staff (id, employee_id, name, email, password, tenant_id) VALUES (500, 'EMP-EXIST', 'Existing', 'e@x.com', 'h', 1)");
        $this->target->exec("INSERT INTO roles (id, name, tenant_id) VALUES (700, 'ExistingRole', 1)");
        $this->source->exec("INSERT INTO staff (id, employee_id, name, email, password) VALUES (1, 'EMP-1', 'Bob', 'bob@example.com', 'hash2')");
        $this->source->exec("INSERT INTO roles (id, name) VALUES (1, 'Teacher')");
        $this->source->exec('INSERT INTO staff_roles (id, staff_id, role_id) VALUES (1, 1, 1)');

        $merger = new MergeStaffData($this->source, $this->target, 2);
        $merger->run();

        $newStaff = $this->target->query("SELECT * FROM staff WHERE name = 'Bob'")->fetch(PDO::FETCH_ASSOC);
        $newRole = $this->target->query("SELECT * FROM roles WHERE name = 'Teacher'")->fetch(PDO::FETCH_ASSOC);

        $this->assertGreaterThan(500, (int) $newStaff['id']);
        $this->assertGreaterThan(700, (int) $newRole['id']);
    }
}
