<?php

use PHPUnit\Framework\TestCase;

final class StudentSessionIdResolverTest extends TestCase
{
    private PDO $source;
    private PDO $target;
    private StudentSessionIdResolver $resolver;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS sessionresolver_test_source');
        $admin->exec('CREATE DATABASE sessionresolver_test_source');
        $admin->exec('DROP DATABASE IF EXISTS sessionresolver_test_target');
        $admin->exec('CREATE DATABASE sessionresolver_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=sessionresolver_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=sessionresolver_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $studentSchema = "id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL, is_active VARCHAR(10) NOT NULL DEFAULT 'yes'";
        $classSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL';
        $sectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL';
        $sessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL,'
            . " created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";

        $this->source->exec("CREATE TABLE students ({$studentSchema})");
        $this->source->exec("CREATE TABLE classes ({$classSchema})");
        $this->source->exec("CREATE TABLE sections ({$sectionSchema})");
        $this->source->exec("CREATE TABLE student_session ({$sessionSchema})");

        $this->target->exec("CREATE TABLE students ({$studentSchema}, tenant_id INT NOT NULL)");
        $this->target->exec("CREATE TABLE classes ({$classSchema}, tenant_id INT NOT NULL)");
        $this->target->exec("CREATE TABLE sections ({$sectionSchema}, tenant_id INT NOT NULL)");
        $this->target->exec("CREATE TABLE student_session ({$sessionSchema}, tenant_id INT NOT NULL)");

        $this->resolver = new StudentSessionIdResolver();
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS sessionresolver_test_source');
        $admin->exec('DROP DATABASE IF EXISTS sessionresolver_test_target');
    }

    public function testResolvesOldSessionIdToNewSessionIdByCompositeKey(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        // created_at pinned identically on both sides -- the resolver's
        // composite key includes created_at, and two separate INSERTs
        // relying on CURRENT_TIMESTAMP's default can straddle a
        // wall-clock second boundary and mismatch, flaking this test.
        $this->source->exec("INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES (401, 101, 201, 301, '2025-01-01 00:00:00')");

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec("INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES (4, 1, 2, 3, 25, '2025-01-01 00:00:00')");

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame([401 => 4], $map);
    }

    public function testUnmatchedSourceSessionIsAbsentFromTheMap(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001'), (102, 'ADM-002')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec(
            "INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES"
            . " (401, 101, 201, 301, '2025-01-01 00:00:00'), (402, 102, 201, 301, '2025-01-01 00:00:00')"
        );

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec("INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES (4, 1, 2, 3, 25, '2025-01-01 00:00:00')");

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame([401 => 4], $map);
        $this->assertArrayNotHasKey(402, $map);
    }

    public function testThrowsWhenSourceHasAmbiguousDuplicateCompositeKey(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        // Two different session rows for the exact same student/class/section
        // triple -- a genuine data-entry duplicate, must throw rather than
        // silently pick one.
        $this->source->exec('INSERT INTO student_session (id, student_id, class_id, section_id) VALUES (401, 101, 201, 301), (402, 101, 201, 301)');

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec('INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id) VALUES (4, 1, 2, 3, 25)');

        $this->expectException(RuntimeException::class);
        $this->resolver->resolve($this->source, $this->target, 25);
    }

    public function testDistinctCreatedAtDisambiguatesSameCompositeKey(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        // Two session rows for the exact same student/class/section triple,
        // at two different points in time -- mirrors the real
        // al_hafeez_campus collision (admission_no 10175/10122: two
        // enrollments in the same class/section a school year apart, same
        // admission_no/class/section, distinct created_at). Must NOT throw,
        // and each old id must resolve to its own matching target id.
        $this->source->exec(
            "INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES"
            . " (401, 101, 201, 301, '2025-07-18 11:21:38'), (402, 101, 201, 301, '2026-02-10 10:42:37')"
        );

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec(
            "INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES"
            . " (4, 1, 2, 3, 25, '2025-07-18 11:21:38'), (5, 1, 2, 3, 25, '2026-02-10 10:42:37')"
        );

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame([401 => 4, 402 => 5], $map);
    }

    public function testDuplicateAdmissionNoWithExactlyOneActiveStudentResolvesToTheActiveOne(): void
    {
        // Mirrors the real nafay_campus collision: two student rows share
        // one admission_no (a data-entry duplicate corrected by
        // deactivating the stale one), both bulk-assigned to the same
        // class/section at the same batch timestamp -- an exact composite
        // key collision. Must resolve to the active student's session row.
        $this->source->exec("INSERT INTO students (id, admission_no, is_active) VALUES (101, 'ADM-001', 'no'), (102, 'ADM-001', 'yes')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec(
            "INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES"
            . " (401, 101, 201, 301, '2026-05-18 11:19:12'), (402, 102, 201, 301, '2026-05-18 11:19:12')"
        );

        $this->target->exec("INSERT INTO students (id, admission_no, is_active, tenant_id) VALUES (1, 'ADM-001', 'yes', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec("INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES (4, 1, 2, 3, 25, '2026-05-18 11:19:12')");

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame([402 => 4], $map);
        $this->assertArrayNotHasKey(401, $map);
    }

    public function testDuplicateAdmissionNoWithNoActiveStudentDropsTheKeyRatherThanThrows(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no, is_active) VALUES (101, 'ADM-001', 'no'), (102, 'ADM-001', 'no')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec(
            "INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES"
            . " (401, 101, 201, 301, '2026-05-18 11:19:12'), (402, 102, 201, 301, '2026-05-18 11:19:12')"
        );

        $this->target->exec("INSERT INTO students (id, admission_no, is_active, tenant_id) VALUES (1, 'ADM-001', 'no', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec("INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES (4, 1, 2, 3, 25, '2026-05-18 11:19:12')");

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame([], $map);
    }
}
