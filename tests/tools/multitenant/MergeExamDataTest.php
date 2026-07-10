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

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE sessions ({$sessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE subjects ({$subjectSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_groups ({$examGroupSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_group_class_batch_exams ({$batchExamSchema}{$tenantCol})");
            $db->exec("CREATE TABLE exam_group_class_batch_exam_subjects ({$batchExamSubjectSchema}{$tenantCol})");
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
}
