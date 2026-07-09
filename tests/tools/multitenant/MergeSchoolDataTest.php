<?php

use PHPUnit\Framework\TestCase;

final class MergeSchoolDataTest extends TestCase
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

        $studentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, parent_id INT NOT NULL DEFAULT 0, firstname VARCHAR(100) NOT NULL';
        $this->source->exec("CREATE TABLE students ({$studentSchema})");
        $this->target->exec("CREATE TABLE students ({$studentSchema}, tenant_id INT NOT NULL)");

        $userSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL DEFAULT 0, username VARCHAR(50) NOT NULL';
        $this->source->exec("CREATE TABLE users ({$userSchema})");
        $this->target->exec("CREATE TABLE users ({$userSchema}, tenant_id INT NOT NULL)");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_test_target');
    }

    public function testMergesStudentsAndUsersPreservingCircularReference(): void
    {
        $this->source->exec("INSERT INTO users (id, user_id, username) VALUES (1, 0, 'parent1')");
        $this->source->exec("INSERT INTO students (id, parent_id, firstname) VALUES (1, 1, 'Alice')");
        $this->source->exec('UPDATE users SET user_id = 1 WHERE id = 1');

        $merger = new MergeSchoolData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['students_migrated']);
        $this->assertSame(1, $result['users_migrated']);

        $student = $this->target->query('SELECT * FROM students')->fetch(PDO::FETCH_ASSOC);
        $user = $this->target->query('SELECT * FROM users')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('Alice', $student['firstname']);
        $this->assertSame(25, (int) $student['tenant_id']);
        $this->assertSame(25, (int) $user['tenant_id']);
        $this->assertSame((int) $user['id'], (int) $student['parent_id']);
        $this->assertSame((int) $student['id'], (int) $user['user_id']);
    }

    public function testStartsIdsAfterExistingTargetRowsToAvoidCollision(): void
    {
        $this->target->exec("INSERT INTO students (id, parent_id, firstname, tenant_id) VALUES (500, 0, 'Existing', 1)");
        $this->source->exec("INSERT INTO users (id, user_id, username) VALUES (1, 0, 'parent1')");
        $this->source->exec("INSERT INTO students (id, parent_id, firstname) VALUES (1, 1, 'Bob')");

        $merger = new MergeSchoolData($this->source, $this->target, 2);
        $merger->run();

        $newStudent = $this->target->query("SELECT * FROM students WHERE firstname = 'Bob'")->fetch(PDO::FETCH_ASSOC);
        $this->assertGreaterThan(500, (int) $newStudent['id']);
    }
}
