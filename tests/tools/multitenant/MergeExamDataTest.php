<?php

use PHPUnit\Framework\TestCase;

final class MergeExamDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_exam_test_source');
        $admin->exec('CREATE DATABASE merge_exam_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_exam_test_target');
        $admin->exec('CREATE DATABASE merge_exam_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_exam_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_exam_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, session VARCHAR(60) DEFAULT NULL, is_active VARCHAR(255) DEFAULT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $subjectSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) DEFAULT NULL, code VARCHAR(100) NOT NULL,'
            . ' type VARCHAR(100) NOT NULL, is_active VARCHAR(255) DEFAULT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $examGroupSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(250) DEFAULT NULL, exam_type VARCHAR(250) DEFAULT NULL,'
            . ' description TEXT DEFAULT NULL, is_active INT DEFAULT 1,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $batchExamSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, exam VARCHAR(250) DEFAULT NULL, passing_percentage FLOAT(10,2) DEFAULT NULL,'
            . ' session_id INT NOT NULL, date_from DATE DEFAULT NULL, date_to DATE DEFAULT NULL, exam_group_id INT DEFAULT NULL,'
            . ' use_exam_roll_no INT NOT NULL DEFAULT 1, is_publish INT DEFAULT 0, is_rank_generated INT NOT NULL DEFAULT 0,'
            . ' description TEXT DEFAULT NULL, is_active INT DEFAULT 0,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $batchExamSubjectSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, exam_group_class_batch_exams_id INT DEFAULT NULL, subject_id INT NOT NULL,'
            . ' date_from DATE NOT NULL, time_from TIME NOT NULL, duration VARCHAR(50) NOT NULL, room_no VARCHAR(100) DEFAULT NULL,'
            . ' max_marks FLOAT(10,2) DEFAULT NULL, min_marks FLOAT(10,2) DEFAULT NULL, credit_hours FLOAT(10,2) DEFAULT 0.00,'
            . ' date_to DATETIME DEFAULT NULL, is_active INT DEFAULT 0,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';

        $studentSchema = "id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL, is_active VARCHAR(10) NOT NULL DEFAULT 'yes'";
        $classSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL';
        $sectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL';
        $studentSessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL,'
            . " created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $batchExamStudentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, exam_group_class_batch_exam_id INT NOT NULL, student_id INT NOT NULL,'
            . ' student_session_id INT NOT NULL, roll_no INT DEFAULT NULL, teacher_remark TEXT DEFAULT NULL, `rank` INT NOT NULL DEFAULT 0,'
            . ' is_active INT DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $resultSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, exam_group_class_batch_exam_student_id INT NOT NULL,'
            . ' exam_group_class_batch_exam_subject_id INT DEFAULT NULL, attendence VARCHAR(10) DEFAULT NULL, get_marks FLOAT(10,2) DEFAULT 0.00,'
            . ' note TEXT DEFAULT NULL, is_active INT DEFAULT 0,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE sessions ({$sessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE subjects ({$subjectSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_groups ({$examGroupSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_group_class_batch_exams ({$batchExamSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_group_class_batch_exam_subjects ({$batchExamSubjectSchema}{$tenantCol})");
            $db->exec("CREATE TABLE students ({$studentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE classes ({$classSchema}{$tenantCol})");
            $db->exec("CREATE TABLE sections ({$sectionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_session ({$studentSessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_group_class_batch_exam_students ({$batchExamStudentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_group_exam_results ({$resultSchema}{$tenantCol})");
        }
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_exam_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_exam_test_target');
    }

    public function testMergesCatalogAndScheduleTablesWithRemappedIds(): void
    {
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->source->exec("INSERT INTO subjects (id, name, code, type) VALUES (5, 'Mathematics', '', 'theory')");
        $this->source->exec("INSERT INTO exam_groups (id, name, exam_type) VALUES (8, 'Annual Terminal Examination', 'school_grade_system')");
        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exams (id, exam, session_id, exam_group_id) VALUES (30, '9th Annual', 20, 8)"
        );
        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exam_subjects (id, exam_group_class_batch_exams_id, subject_id, date_from, time_from, duration)"
            . " VALUES (100, 30, 5, '2026-03-01', '09:00:00', '2 hours')"
        );

        $merger = new MergeExamData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['sessions_migrated']);
        $this->assertSame(1, $result['subjects_migrated']);
        $this->assertSame(1, $result['exam_groups_migrated']);
        $this->assertSame(1, $result['exam_group_class_batch_exams_migrated']);
        $this->assertSame(1, $result['exam_group_class_batch_exam_subjects_migrated']);

        $session = $this->target->query('SELECT * FROM sessions')->fetch(PDO::FETCH_ASSOC);
        $subject = $this->target->query('SELECT * FROM subjects')->fetch(PDO::FETCH_ASSOC);
        $examGroup = $this->target->query('SELECT * FROM exam_groups')->fetch(PDO::FETCH_ASSOC);
        $batchExam = $this->target->query('SELECT * FROM exam_group_class_batch_exams')->fetch(PDO::FETCH_ASSOC);
        $batchExamSubject = $this->target->query('SELECT * FROM exam_group_class_batch_exam_subjects')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame('2024-25', $session['session']);
        $this->assertSame(25, (int) $session['tenant_id']);
        $this->assertSame('Mathematics', $subject['name']);
        $this->assertSame('Annual Terminal Examination', $examGroup['name']);
        $this->assertSame((int) $session['id'], (int) $batchExam['session_id']);
        $this->assertSame((int) $examGroup['id'], (int) $batchExam['exam_group_id']);
        $this->assertSame(25, (int) $batchExam['tenant_id']);
        $this->assertSame((int) $batchExam['id'], (int) $batchExamSubject['exam_group_class_batch_exams_id']);
        $this->assertSame((int) $subject['id'], (int) $batchExamSubject['subject_id']);
        $this->assertSame(25, (int) $batchExamSubject['tenant_id']);
    }

    public function testReconnectsExamStudentsAndResultsToAlreadyMigratedData(): void
    {
        // Catalog + schedule chain (same shape as the Part A test).
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->source->exec("INSERT INTO exam_groups (id, name) VALUES (8, 'Annual Terminal Examination')");
        $this->source->exec("INSERT INTO exam_group_class_batch_exams (id, exam, session_id, exam_group_id) VALUES (30, '9th Annual', 20, 8)");
        $this->source->exec("INSERT INTO subjects (id, name, code, type) VALUES (5, 'Mathematics', '', 'theory')");
        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exam_subjects (id, exam_group_class_batch_exams_id, subject_id, date_from, time_from, duration)"
            . " VALUES (100, 30, 5, '2026-03-01', '09:00:00', '2 hours')"
        );

        // Student/session chain -- old ids in source (100s/400s), already
        // migrated NEW ids in target (1s/4s), deliberately non-overlapping
        // so a bug using the wrong id is obvious.
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec("INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES (401, 101, 201, 301, '2025-01-01 00:00:00')");

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec("INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES (4, 1, 2, 3, 25, '2025-01-01 00:00:00')");

        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exam_students (id, exam_group_class_batch_exam_id, student_id, student_session_id, roll_no)"
            . " VALUES (500, 30, 101, 401, 7)"
        );
        $this->source->exec(
            "INSERT INTO exam_group_exam_results (id, exam_group_class_batch_exam_student_id, exam_group_class_batch_exam_subject_id, attendence, get_marks)"
            . " VALUES (900, 500, 100, 'present', 88.5)"
        );

        $merger = new MergeExamData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['exam_group_class_batch_exam_students_migrated']);
        $this->assertSame(1, $result['exam_group_class_batch_exam_students_source_total']);
        $this->assertSame(0, $result['exam_group_class_batch_exam_students_skipped']);
        $this->assertSame(1, $result['exam_group_exam_results_migrated']);
        $this->assertSame(1, $result['exam_group_exam_results_source_total']);
        $this->assertSame(0, $result['exam_group_exam_results_skipped']);

        $examStudent = $this->target->query('SELECT * FROM exam_group_class_batch_exam_students')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int) $examStudent['student_id']);
        $this->assertSame(4, (int) $examStudent['student_session_id']);
        $this->assertSame(7, (int) $examStudent['roll_no']);
        $this->assertSame(25, (int) $examStudent['tenant_id']);

        $result_row = $this->target->query('SELECT * FROM exam_group_exam_results')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame((int) $examStudent['id'], (int) $result_row['exam_group_class_batch_exam_student_id']);
        $this->assertSame(88.5, (float) $result_row['get_marks']);
        $this->assertSame(25, (int) $result_row['tenant_id']);
    }

    public function testAssignsFreshTargetIdsToResultsInsteadOfReusingSourceIds(): void
    {
        // Same chain as the reconnect test, but the TARGET already has an
        // unrelated exam_group_exam_results row sitting at id 900 -- the
        // exact id the source row about to be migrated also uses. If
        // results kept their source id unchanged (the bug this test
        // guards against), inserting the migrated row would violate the
        // target's PRIMARY KEY and this test would fail with a PDO
        // exception instead of reaching the assertions below.
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->source->exec("INSERT INTO exam_groups (id, name) VALUES (8, 'Annual Terminal Examination')");
        $this->source->exec("INSERT INTO exam_group_class_batch_exams (id, exam, session_id, exam_group_id) VALUES (30, '9th Annual', 20, 8)");
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec("INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES (401, 101, 201, 301, '2025-01-01 00:00:00')");
        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exam_students (id, exam_group_class_batch_exam_id, student_id, student_session_id, roll_no)"
            . " VALUES (500, 30, 101, 401, 7)"
        );
        $this->source->exec(
            "INSERT INTO exam_group_exam_results (id, exam_group_class_batch_exam_student_id, attendence, get_marks) VALUES (900, 500, 'present', 88.5)"
        );

        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec("INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES (4, 1, 2, 3, 25, '2025-01-01 00:00:00')");
        // Pre-existing, unrelated target rows occupying id 900 on both the
        // parent link table and the results table itself. Belongs to a
        // DIFFERENT tenant (99, not 25) deliberately: nextId() computes
        // MAX(id)+1 table-wide, not scoped by tenant, so the id-collision
        // this test guards against is exercised regardless of which tenant
        // owns the colliding row. Using tenant 25 here would be an
        // unrealistic fixture now that the re-run guard exists -- tenant
        // 25's own exam_group_exam_results/exam_group_class_batch_exam_students
        // rows are exclusively populated by this tool's own run, so there is
        // no legitimate way for tenant 25 to have pre-existing rows there
        // before this tool's first run.
        $this->target->exec(
            "INSERT INTO exam_group_class_batch_exam_students (id, exam_group_class_batch_exam_id, student_id, student_session_id, tenant_id) VALUES (900, 1, 1, 4, 99)"
        );
        $this->target->exec(
            "INSERT INTO exam_group_exam_results (id, exam_group_class_batch_exam_student_id, get_marks, tenant_id) VALUES (900, 900, 10, 99)"
        );

        $merger = new MergeExamData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['exam_group_exam_results_migrated']);
        $count = (int) $this->target->query('SELECT COUNT(*) FROM exam_group_exam_results')->fetchColumn();
        $this->assertSame(2, $count);

        $migratedRow = $this->target->query(
            "SELECT * FROM exam_group_exam_results WHERE get_marks = 88.5"
        )->fetch(PDO::FETCH_ASSOC);
        $this->assertNotSame(900, (int) $migratedRow['id']);
    }

    public function testSkipsExamStudentRowsReferencingAnUnmigratedStudent(): void
    {
        $this->source->exec("INSERT INTO exam_groups (id, name) VALUES (8, 'Annual Terminal Examination')");
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->source->exec("INSERT INTO exam_group_class_batch_exams (id, exam, session_id, exam_group_id) VALUES (30, '9th Annual', 20, 8)");
        // student_id 999 / student_session_id 999 have no corresponding rows
        // anywhere -- simulates a dangling reference that must be skipped,
        // not inserted broken, and must be counted.
        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exam_students (id, exam_group_class_batch_exam_id, student_id, student_session_id, roll_no)"
            . " VALUES (500, 30, 999, 999, 1)"
        );
        $this->source->exec(
            "INSERT INTO exam_group_exam_results (id, exam_group_class_batch_exam_student_id, attendence, get_marks) VALUES (900, 500, 'present', 50)"
        );

        $merger = new MergeExamData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(0, $result['exam_group_class_batch_exam_students_migrated']);
        $this->assertSame(1, $result['exam_group_class_batch_exam_students_source_total']);
        $this->assertSame(1, $result['exam_group_class_batch_exam_students_skipped']);
        // The result row's own parent link never got migrated, so it must
        // cascade-skip too, not orphan-insert.
        $this->assertSame(0, $result['exam_group_exam_results_migrated']);
        $this->assertSame(1, $result['exam_group_exam_results_source_total']);
        $this->assertSame(1, $result['exam_group_exam_results_skipped']);
    }

    public function testRefusesToRunAgainIfTenantAlreadyHasSessionRows(): void
    {
        $this->target->exec("INSERT INTO sessions (id, session, tenant_id) VALUES (1, 'Existing', 25)");

        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->source->exec("INSERT INTO subjects (id, name, code, type) VALUES (5, 'Mathematics', '', 'theory')");
        $this->source->exec("INSERT INTO exam_groups (id, name, exam_type) VALUES (8, 'Annual Terminal Examination', 'school_grade_system')");
        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exams (id, exam, session_id, exam_group_id) VALUES (30, '9th Annual', 20, 8)"
        );
        $this->source->exec(
            "INSERT INTO exam_group_class_batch_exam_subjects (id, exam_group_class_batch_exams_id, subject_id, date_from, time_from, duration)"
            . " VALUES (100, 30, 5, '2026-03-01', '09:00:00', '2 hours')"
        );

        $merger = new MergeExamData($this->source, $this->target, 25);

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('sessions', $e->getMessage());
            $this->assertStringContainsString('25', $e->getMessage());
        }

        $this->assertTrue($threw, 'Expected run() to refuse when tenant 25 already has sessions rows');

        $sessionCount = (int) $this->target->query("SELECT COUNT(*) AS c FROM sessions WHERE tenant_id = 25")->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(1, $sessionCount, 'Refusing to run must not insert any new rows -- only the pre-existing row should remain');
    }
}
