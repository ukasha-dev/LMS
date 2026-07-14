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

        // Matches the exact column set MergeSchoolData::run() selects (see
        // its whitelist SELECT below) and school_saas's real schema (Task
        // 4) — not just the two columns the assertions happen to check.
        $studentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, parent_id INT NOT NULL DEFAULT 0,'
            . ' admission_no VARCHAR(100) DEFAULT NULL, firstname VARCHAR(100) NOT NULL,'
            . ' middlename VARCHAR(255) DEFAULT NULL, lastname VARCHAR(100) DEFAULT NULL,'
            . " is_active VARCHAR(255) DEFAULT 'yes'";
        $this->source->exec("CREATE TABLE students ({$studentSchema})");
        $this->target->exec("CREATE TABLE students ({$studentSchema}, tenant_id INT NOT NULL)");

        $userSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL DEFAULT 0,'
            . ' username VARCHAR(50) NOT NULL, password VARCHAR(255) DEFAULT NULL,'
            . " role VARCHAR(30) NOT NULL DEFAULT 'parent', is_active VARCHAR(255) DEFAULT 'yes',"
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
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

    public function testRollsBackTransactionOnInsertFailure(): void
    {
        // Force a genuine mid-transaction failure without racing nextId():
        // nextId() is queried fresh by run() itself and always returns an
        // id one past the current MAX, so pre-inserting a "colliding" row
        // into the target before calling run() only shifts nextId() past
        // it (that's exactly what testStartsIdsAfterExistingTargetRowsTo-
        // AvoidCollision proves happens) — it can never reproduce a real
        // primary-key collision. Instead, add a UNIQUE constraint on the
        // target's admission_no column only, and give two source students
        // the same admission_no: the first insert succeeds, the second
        // throws a duplicate-key PDOException inside the try block, before
        // the users loop even starts.
        $this->target->exec('ALTER TABLE students ADD UNIQUE KEY uniq_admission_no (admission_no)');

        $this->source->exec("INSERT INTO users (id, user_id, username) VALUES (1, 0, 'parent1')");
        $this->source->exec("INSERT INTO students (id, parent_id, admission_no, firstname) VALUES (1, 1, 'DUPE001', 'Alice')");
        $this->source->exec("INSERT INTO students (id, parent_id, admission_no, firstname) VALUES (2, 1, 'DUPE001', 'Bob')");
        $this->source->exec('UPDATE users SET user_id = 1 WHERE id = 1');

        $merger = new MergeSchoolData($this->source, $this->target, 25);

        $threw = false;
        try {
            $merger->run();
        } catch (Throwable $e) {
            $threw = true;
            $this->assertInstanceOf(PDOException::class, $e);
        }

        $this->assertTrue($threw, 'Expected run() to throw on duplicate-key insert');

        $studentCount = (int) $this->target->query('SELECT COUNT(*) AS c FROM students')->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(0, $studentCount, 'Transaction should have rolled back the already-inserted first student row too');

        $userCount = (int) $this->target->query('SELECT COUNT(*) AS c FROM users')->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(0, $userCount, 'Transaction should have rolled back, leaving users table empty');
    }

    public function testRefusesToRunAgainIfTenantAlreadyHasStudentRows(): void
    {
        $this->target->exec("INSERT INTO students (id, parent_id, firstname, tenant_id) VALUES (1, 0, 'Existing', 25)");

        $this->source->exec("INSERT INTO users (id, user_id, username) VALUES (1, 0, 'parent1')");
        $this->source->exec("INSERT INTO students (id, parent_id, firstname) VALUES (1, 1, 'Bob')");

        $merger = new MergeSchoolData($this->source, $this->target, 25);

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('students', $e->getMessage());
            $this->assertStringContainsString('25', $e->getMessage());
        }

        $this->assertTrue($threw, 'Expected run() to refuse when tenant 25 already has student rows');

        $studentCount = (int) $this->target->query("SELECT COUNT(*) AS c FROM students WHERE tenant_id = 25")->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(1, $studentCount, 'Refusing to run must not insert any new rows -- only the pre-existing row should remain');
    }

    public function testGuardDoesNotFalselyBlockADifferentTenantWithNoExistingRows(): void
    {
        $this->target->exec("INSERT INTO students (id, parent_id, firstname, tenant_id) VALUES (1, 0, 'OtherTenant', 99)");

        $this->source->exec("INSERT INTO users (id, user_id, username) VALUES (1, 0, 'parent1')");
        $this->source->exec("INSERT INTO students (id, parent_id, firstname) VALUES (1, 1, 'Bob')");

        $merger = new MergeSchoolData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['students_migrated'], 'Tenant 25 has no existing rows -- the guard must not block it just because tenant 99 has data');
    }
}
