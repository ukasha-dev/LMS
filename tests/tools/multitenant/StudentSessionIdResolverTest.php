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

        $studentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL';
        $classSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL';
        $sectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL';
        $sessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL,'
            . " is_active VARCHAR(255) DEFAULT 'yes'";

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
        $this->source->exec('INSERT INTO student_session (id, student_id, class_id, section_id) VALUES (401, 101, 201, 301)');

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec('INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id) VALUES (4, 1, 2, 3, 25)');

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame([401 => 4], $map);
    }

    public function testUnmatchedSourceSessionIsAbsentFromTheMap(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001'), (102, 'ADM-002')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec('INSERT INTO student_session (id, student_id, class_id, section_id) VALUES (401, 101, 201, 301), (402, 102, 201, 301)');

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec('INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id) VALUES (4, 1, 2, 3, 25)');

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

    public function testExcludesInactiveSessionsFromBothTheMapAndCollisionDetection(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        // Two INACTIVE session rows for the exact same student/class/section
        // triple -- mirrors the real al_hafeez_campus collision (admission_no
        // 10175/10122, both duplicate rows is_active='no'). Must NOT throw,
        // and neither row should appear in the result map.
        $this->source->exec(
            "INSERT INTO student_session (id, student_id, class_id, section_id, is_active) VALUES (401, 101, 201, 301, 'no'), (402, 101, 201, 301, 'no')"
        );

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame([], $map);
    }
}
