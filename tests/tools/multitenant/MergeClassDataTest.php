<?php

use PHPUnit\Framework\TestCase;

final class MergeClassDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_class_test_source');
        $admin->exec('CREATE DATABASE merge_class_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_class_test_target');
        $admin->exec('CREATE DATABASE merge_class_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_class_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_class_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $classSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL,'
            . " is_active VARCHAR(255) DEFAULT 'no',"
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE classes ({$classSchema})");
        $this->target->exec("CREATE TABLE classes ({$classSchema}, tenant_id INT NOT NULL)");

        $sectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL,'
            . " is_active VARCHAR(255) DEFAULT 'no',"
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE sections ({$sectionSchema})");
        $this->target->exec("CREATE TABLE sections ({$sectionSchema}, tenant_id INT NOT NULL)");

        $classSectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class_id INT NOT NULL, section_id INT NOT NULL,'
            . " is_active VARCHAR(255) DEFAULT 'no',"
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . ' updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP';
        $this->source->exec("CREATE TABLE class_sections ({$classSectionSchema})");
        $this->target->exec("CREATE TABLE class_sections ({$classSectionSchema}, tenant_id INT NOT NULL)");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_class_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_class_test_target');
    }

    public function testMergesClassesSectionsAndClassSectionsWithRemappedForeignKeys(): void
    {
        $this->source->exec("INSERT INTO classes (id, class) VALUES (1, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (1, 'A')");
        $this->source->exec('INSERT INTO class_sections (id, class_id, section_id) VALUES (1, 1, 1)');

        $merger = new MergeClassData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['classes_migrated']);
        $this->assertSame(1, $result['sections_migrated']);
        $this->assertSame(1, $result['class_sections_migrated']);

        $class = $this->target->query('SELECT * FROM classes')->fetch(PDO::FETCH_ASSOC);
        $section = $this->target->query('SELECT * FROM sections')->fetch(PDO::FETCH_ASSOC);
        $classSection = $this->target->query('SELECT * FROM class_sections')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('Class 1', $class['class']);
        $this->assertSame(25, (int) $class['tenant_id']);
        $this->assertSame(25, (int) $section['tenant_id']);
        $this->assertSame(25, (int) $classSection['tenant_id']);
        $this->assertSame((int) $class['id'], (int) $classSection['class_id']);
        $this->assertSame((int) $section['id'], (int) $classSection['section_id']);
    }

    public function testStartsClassAndSectionIdsAfterExistingTargetRowsToAvoidCollision(): void
    {
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (500, 'Existing', 1)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (700, 'Existing', 1)");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (1, 'Class 2')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (1, 'B')");
        $this->source->exec('INSERT INTO class_sections (id, class_id, section_id) VALUES (1, 1, 1)');

        $merger = new MergeClassData($this->source, $this->target, 2);
        $merger->run();

        $newClass = $this->target->query("SELECT * FROM classes WHERE class = 'Class 2'")->fetch(PDO::FETCH_ASSOC);
        $newSection = $this->target->query("SELECT * FROM sections WHERE section = 'B'")->fetch(PDO::FETCH_ASSOC);

        $this->assertGreaterThan(500, (int) $newClass['id']);
        $this->assertGreaterThan(700, (int) $newSection['id']);
    }
}
