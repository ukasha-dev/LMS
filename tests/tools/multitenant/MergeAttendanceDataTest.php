<?php

use PHPUnit\Framework\TestCase;

final class MergeAttendanceDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_attendance_test_source');
        $admin->exec('CREATE DATABASE merge_attendance_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_attendance_test_target');
        $admin->exec('CREATE DATABASE merge_attendance_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_attendance_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_attendance_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $studentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL';
        $classSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL';
        $sectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL';
        $sessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL';
        $typeSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, type VARCHAR(50) DEFAULT NULL, key_value VARCHAR(50) NOT NULL,'
            . ' long_lang_name VARCHAR(250) DEFAULT NULL, long_name_style VARCHAR(250) DEFAULT NULL,'
            . " is_active VARCHAR(255) DEFAULT NULL, for_qr_attendance INT NOT NULL DEFAULT 1, for_schedule INT NOT NULL DEFAULT 0,"
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $attendanceSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_session_id INT NOT NULL, date DATE DEFAULT NULL,'
            . ' attendence_type_id INT NOT NULL, remark VARCHAR(200) DEFAULT NULL, is_active VARCHAR(255) DEFAULT NULL,'
            . ' in_time TIME DEFAULT NULL, out_time TIME DEFAULT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE students ({$studentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE classes ({$classSchema}{$tenantCol})");
            $db->exec("CREATE TABLE sections ({$sectionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_session ({$sessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE attendence_type ({$typeSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_attendences ({$attendanceSchema}{$tenantCol})");
        }
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_attendance_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_attendance_test_target');
    }

    public function testMergesAttendanceTypesAndRecordsWithResolvedSessionAndType(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec('INSERT INTO student_session (id, student_id, class_id, section_id) VALUES (401, 101, 201, 301)');
        $this->source->exec("INSERT INTO attendence_type (id, type, key_value) VALUES (1, 'Present', 'P')");
        $this->source->exec("INSERT INTO student_attendences (student_session_id, date, attendence_type_id, remark) VALUES (401, '2026-01-15', 1, '')");

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec('INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id) VALUES (4, 1, 2, 3, 25)');

        $merger = new MergeAttendanceData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['attendence_types_migrated']);
        $this->assertSame(1, $result['student_attendences_migrated']);

        $type = $this->target->query('SELECT * FROM attendence_type')->fetch(PDO::FETCH_ASSOC);
        $attendance = $this->target->query('SELECT * FROM student_attendences')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('Present', $type['type']);
        $this->assertSame(25, (int) $type['tenant_id']);
        $this->assertSame(4, (int) $attendance['student_session_id']);
        $this->assertSame((int) $type['id'], (int) $attendance['attendence_type_id']);
        $this->assertSame(25, (int) $attendance['tenant_id']);
        $this->assertSame('2026-01-15', $attendance['date']);
    }

    public function testSkipsAttendanceRowsReferencingAnUnmigratedStudentSession(): void
    {
        $this->source->exec("INSERT INTO attendence_type (id, type, key_value) VALUES (1, 'Present', 'P')");
        // student_session_id 999 has no corresponding row anywhere -- a
        // dangling reference that must be skipped, not inserted broken.
        $this->source->exec("INSERT INTO student_attendences (student_session_id, date, attendence_type_id, remark) VALUES (999, '2026-01-15', 1, '')");

        $merger = new MergeAttendanceData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['attendence_types_migrated']);
        $this->assertSame(0, $result['student_attendences_migrated']);
        $count = (int) $this->target->query('SELECT COUNT(*) FROM student_attendences')->fetchColumn();
        $this->assertSame(0, $count);
    }
}
