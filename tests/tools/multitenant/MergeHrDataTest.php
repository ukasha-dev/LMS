<?php

use PHPUnit\Framework\TestCase;

final class MergeHrDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_hr_test_source');
        $admin->exec('CREATE DATABASE merge_hr_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_hr_test_target');
        $admin->exec('CREATE DATABASE merge_hr_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_hr_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_hr_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $departmentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, department_name VARCHAR(200) NOT NULL, is_active VARCHAR(100) NOT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $designationSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, designation VARCHAR(200) NOT NULL, is_active VARCHAR(100) NOT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $leaveTypeSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, type VARCHAR(200) NOT NULL, is_active VARCHAR(50) NOT NULL';
        $staffSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(200) NOT NULL';
        $sldSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, staff_id INT NOT NULL, leave_type_id INT NOT NULL, alloted_leave VARCHAR(100) NOT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE department ({$departmentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE staff_designation ({$designationSchema}{$tenantCol})");
            $db->exec("CREATE TABLE leave_types ({$leaveTypeSchema}{$tenantCol})");
            $db->exec("CREATE TABLE staff ({$staffSchema}{$tenantCol})");
            $db->exec("CREATE TABLE staff_leave_details ({$sldSchema}{$tenantCol})");
        }
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_hr_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_hr_test_target');
    }

    public function testMergesCatalogTablesAndReconnectsStaffLeaveDetails(): void
    {
        $this->source->exec("INSERT INTO department (id, department_name, is_active) VALUES (3, 'Academics', '1')");
        $this->source->exec("INSERT INTO staff_designation (id, designation, is_active) VALUES (7, 'Teacher', '1')");
        $this->source->exec("INSERT INTO leave_types (id, type, is_active) VALUES (2, 'Sick Leave', '1')");

        // Staff old id (100) in source, already-migrated new id (5) in
        // target, deliberately non-overlapping so a bug using the wrong
        // id is obvious.
        $this->source->exec("INSERT INTO staff (id, email) VALUES (100, 'teacher@example.com')");
        $this->target->exec("INSERT INTO staff (id, email, tenant_id) VALUES (5, 'teacher@example.com', 25)");

        $this->source->exec(
            "INSERT INTO staff_leave_details (id, staff_id, leave_type_id, alloted_leave) VALUES (500, 100, 2, '10')"
        );

        $merger = new MergeHrData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['department_migrated']);
        $this->assertSame(1, $result['staff_designation_migrated']);
        $this->assertSame(1, $result['leave_types_migrated']);
        $this->assertSame(1, $result['staff_leave_details_migrated']);
        $this->assertSame(1, $result['staff_leave_details_source_total']);
        $this->assertSame(0, $result['staff_leave_details_skipped']);

        $department = $this->target->query('SELECT * FROM department')->fetch(PDO::FETCH_ASSOC);
        $designation = $this->target->query('SELECT * FROM staff_designation')->fetch(PDO::FETCH_ASSOC);
        $leaveType = $this->target->query('SELECT * FROM leave_types')->fetch(PDO::FETCH_ASSOC);
        $sld = $this->target->query('SELECT * FROM staff_leave_details')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('Academics', $department['department_name']);
        $this->assertSame(25, (int) $department['tenant_id']);
        $this->assertSame('Teacher', $designation['designation']);
        $this->assertSame('Sick Leave', $leaveType['type']);
        $this->assertSame(5, (int) $sld['staff_id']);
        $this->assertSame((int) $leaveType['id'], (int) $sld['leave_type_id']);
        $this->assertSame('10', $sld['alloted_leave']);
        $this->assertSame(25, (int) $sld['tenant_id']);
    }

    public function testSkipsLeaveDetailsReferencingAnUnmigratedStaffMember(): void
    {
        $this->source->exec("INSERT INTO leave_types (id, type, is_active) VALUES (2, 'Sick Leave', '1')");
        // staff_id 999 has no corresponding row anywhere -- simulates a
        // dangling reference that must be skipped, not inserted broken.
        $this->source->exec(
            "INSERT INTO staff_leave_details (id, staff_id, leave_type_id, alloted_leave) VALUES (500, 999, 2, '10')"
        );

        $merger = new MergeHrData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(0, $result['staff_leave_details_migrated']);
        $this->assertSame(1, $result['staff_leave_details_source_total']);
        $this->assertSame(1, $result['staff_leave_details_skipped']);
    }

    public function testRefusesToRunAgainIfTenantAlreadyHasDepartmentRows(): void
    {
        $this->target->exec("INSERT INTO department (id, department_name, is_active, tenant_id) VALUES (1, 'Existing', '1', 25)");

        $this->source->exec("INSERT INTO department (id, department_name, is_active) VALUES (3, 'Academics', '1')");
        $this->source->exec("INSERT INTO staff_designation (id, designation, is_active) VALUES (7, 'Teacher', '1')");
        $this->source->exec("INSERT INTO leave_types (id, type, is_active) VALUES (2, 'Sick Leave', '1')");
        $this->source->exec("INSERT INTO staff (id, email) VALUES (100, 'teacher@example.com')");
        $this->target->exec("INSERT INTO staff (id, email, tenant_id) VALUES (5, 'teacher@example.com', 25)");
        $this->source->exec(
            "INSERT INTO staff_leave_details (id, staff_id, leave_type_id, alloted_leave) VALUES (500, 100, 2, '10')"
        );

        $merger = new MergeHrData($this->source, $this->target, 25);

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('department', $e->getMessage());
            $this->assertStringContainsString('25', $e->getMessage());
        }

        $this->assertTrue($threw, 'Expected run() to refuse when tenant 25 already has department rows');

        $departmentCount = (int) $this->target->query("SELECT COUNT(*) AS c FROM department WHERE tenant_id = 25")->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(1, $departmentCount, 'Refusing to run must not insert any new rows -- only the pre-existing row should remain');
    }
}
