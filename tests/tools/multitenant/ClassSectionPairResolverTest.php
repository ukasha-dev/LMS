<?php

use PHPUnit\Framework\TestCase;

final class ClassSectionPairResolverTest extends TestCase
{
    private PDO $source;
    private PDO $target;
    private ClassSectionPairResolver $resolver;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS pairresolver_test_source');
        $admin->exec('CREATE DATABASE pairresolver_test_source');
        $admin->exec('DROP DATABASE IF EXISTS pairresolver_test_target');
        $admin->exec('CREATE DATABASE pairresolver_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=pairresolver_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=pairresolver_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $classSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL';
        $sectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL';
        $csSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class_id INT NOT NULL, section_id INT NOT NULL';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE classes ({$classSchema}{$tenantCol})");
            $db->exec("CREATE TABLE sections ({$sectionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE class_sections ({$csSchema}{$tenantCol})");
        }

        $this->resolver = new ClassSectionPairResolver();
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS pairresolver_test_source');
        $admin->exec('DROP DATABASE IF EXISTS pairresolver_test_target');
    }

    public function testResolvesSameSectionIdSharedAcrossMultipleClasses(): void
    {
        // Mirrors real al_hafeez_campus data: section id 20 "Green 05" used
        // by both Class 1 and Class 2, via two different class_sections rows.
        $this->source->exec("INSERT INTO classes (id, class) VALUES (1, 'Class 1'), (2, 'Class 2')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (20, 'Green 05')");
        $this->source->exec('INSERT INTO class_sections (class_id, section_id) VALUES (1, 20), (2, 20)');

        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (100, 'Class 1', 25), (101, 'Class 2', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (200, 'Green 05', 25)");
        $this->target->exec('INSERT INTO class_sections (class_id, section_id, tenant_id) VALUES (100, 200, 25), (101, 200, 25)');

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame(['class_id' => 100, 'section_id' => 200], $map['1:20']);
        $this->assertSame(['class_id' => 101, 'section_id' => 200], $map['2:20']);
    }

    public function testResolvesTwoDistinctSectionsSharingTheSameNameForDifferentClasses(): void
    {
        // Mirrors real al_hafeez_campus data: a SEPARATE section row (id 43)
        // also named "Green 05", used only by Class 3 — must resolve
        // independently of section id 20's mapping above.
        $this->source->exec("INSERT INTO classes (id, class) VALUES (1, 'Class 1'), (3, 'Class 3')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (20, 'Green 05'), (43, 'Green 05')");
        $this->source->exec('INSERT INTO class_sections (class_id, section_id) VALUES (1, 20), (3, 43)');

        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (100, 'Class 1', 25), (102, 'Class 3', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (200, 'Green 05', 25), (201, 'Green 05', 25)");
        $this->target->exec('INSERT INTO class_sections (class_id, section_id, tenant_id) VALUES (100, 200, 25), (102, 201, 25)');

        $map = $this->resolver->resolve($this->source, $this->target, 25);

        $this->assertSame(['class_id' => 100, 'section_id' => 200], $map['1:20']);
        $this->assertSame(['class_id' => 102, 'section_id' => 201], $map['3:43']);
    }
}
