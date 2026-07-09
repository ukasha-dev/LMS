<?php

use PHPUnit\Framework\TestCase;

final class MergeStudentSessionDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_session_test_source');
        $admin->exec('CREATE DATABASE merge_session_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_session_test_target');
        $admin->exec('CREATE DATABASE merge_session_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_session_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_session_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Source: the "old" school database shape, unremapped ids.
        $this->source->exec('CREATE TABLE students (id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL)');
        $this->source->exec('CREATE TABLE classes (id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL)');
        $this->source->exec('CREATE TABLE sections (id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL)');
        $this->source->exec('CREATE TABLE student_session (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL, is_active VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP)');

        // Target: school_saas shape, already has these rows under DIFFERENT
        // (already-migrated) ids, matched only by natural key.
        $this->target->exec('CREATE TABLE students (id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL, tenant_id INT NOT NULL)');
        $this->target->exec('CREATE TABLE classes (id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL, tenant_id INT NOT NULL)');
        $this->target->exec('CREATE TABLE sections (id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL, tenant_id INT NOT NULL)');
        $this->target->exec('CREATE TABLE student_session (id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL, is_active VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, tenant_id INT NOT NULL)');
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_session_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_session_test_target');
    }

    public function testReconnectsStudentSessionToAlreadyMigratedRowsByNaturalKey(): void
    {
        // Old ids in source (100s), already-migrated NEW ids in target (1s) —
        // deliberately non-overlapping ranges so a bug that used the wrong
        // id would be obvious.
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec("INSERT INTO student_session (student_id, class_id, section_id, is_active) VALUES (101, 201, 301, 'yes')");

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");

        $merger = new MergeStudentSessionData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['student_session_migrated']);

        $row = $this->target->query('SELECT * FROM student_session')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int) $row['student_id']);
        $this->assertSame(2, (int) $row['class_id']);
        $this->assertSame(3, (int) $row['section_id']);
        $this->assertSame(25, (int) $row['tenant_id']);
    }

    public function testSkipsRowsThatReferenceAStudentNotYetMigrated(): void
    {
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        // student_id 999 has no corresponding students row at all (and
        // definitely no match in target) — simulates a dangling reference.
        $this->source->exec("INSERT INTO student_session (student_id, class_id, section_id, is_active) VALUES (999, 201, 301, 'yes')");

        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");

        $merger = new MergeStudentSessionData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(0, $result['student_session_migrated']);
        $count = (int) $this->target->query('SELECT COUNT(*) FROM student_session')->fetchColumn();
        $this->assertSame(0, $count);
    }
}
