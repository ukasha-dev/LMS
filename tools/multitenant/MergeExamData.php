<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';
require_once __DIR__ . '/NaturalKeyIdResolver.php';
require_once __DIR__ . '/StudentSessionIdResolver.php';

final class MergeExamData extends AbstractTenantMerger
{
    public function run(): array
    {
        $sessionRemap = new IdRemapper($this->nextId('sessions'));
        $sessions = $this->fetchAll('SELECT id, session, is_active, created_at, updated_at FROM sessions');
        foreach ($sessions as $row) {
            $sessionRemap->remapId((int) $row['id']);
        }

        $subjectRemap = new IdRemapper($this->nextId('subjects'));
        $subjects = $this->fetchAll('SELECT id, name, code, type, is_active, created_at, updated_at FROM subjects');
        foreach ($subjects as $row) {
            $subjectRemap->remapId((int) $row['id']);
        }

        $examGroupRemap = new IdRemapper($this->nextId('exam_groups'));
        $examGroups = $this->fetchAll('SELECT id, name, exam_type, description, is_active, created_at, updated_at FROM exam_groups');
        foreach ($examGroups as $row) {
            $examGroupRemap->remapId((int) $row['id']);
        }

        $batchExamRemap = new IdRemapper($this->nextId('exam_group_class_batch_exams'));
        $batchExams = $this->fetchAll(
            'SELECT id, exam, passing_percentage, session_id, date_from, date_to, exam_group_id,'
            . ' use_exam_roll_no, is_publish, is_rank_generated, description, is_active, created_at, updated_at'
            . ' FROM exam_group_class_batch_exams'
        );
        foreach ($batchExams as $row) {
            $batchExamRemap->remapId((int) $row['id']);
        }

        $batchExamSubjectRemap = new IdRemapper($this->nextId('exam_group_class_batch_exam_subjects'));
        $batchExamSubjects = $this->fetchAll(
            'SELECT id, exam_group_class_batch_exams_id, subject_id, date_from, time_from, duration, room_no,'
            . ' max_marks, min_marks, credit_hours, date_to, is_active, created_at, updated_at'
            . ' FROM exam_group_class_batch_exam_subjects'
        );
        foreach ($batchExamSubjects as $row) {
            $batchExamSubjectRemap->remapId((int) $row['id']);
        }

        $studentResolver = new NaturalKeyIdResolver();
        $studentMap = $studentResolver->resolve($this->source, $this->target, $this->tenantId, 'students', 'admission_no');

        $sessionResolver = new StudentSessionIdResolver();
        $studentSessionMap = $sessionResolver->resolve($this->source, $this->target, $this->tenantId);

        $batchExamStudentRemap = new IdRemapper($this->nextId('exam_group_class_batch_exam_students'));
        $batchExamStudents = $this->fetchAll(
            'SELECT id, exam_group_class_batch_exam_id, student_id, student_session_id, roll_no, teacher_remark,'
            . ' `rank`, is_active, created_at, updated_at FROM exam_group_class_batch_exam_students'
        );
        $batchExamStudentSourceTotal = count($batchExamStudents);
        $batchExamStudentSkipped = 0;
        $batchExamStudentRowsToInsert = [];
        foreach ($batchExamStudents as $row) {
            $oldId = (int) $row['id'];
            $oldStudentId = (int) $row['student_id'];
            $oldStudentSessionId = (int) $row['student_session_id'];
            if (!isset($studentMap[$oldStudentId]) || !isset($studentSessionMap[$oldStudentSessionId])) {
                $batchExamStudentSkipped++;
                continue;
            }
            $batchExamStudentRemap->remapId($oldId);
            $row['id'] = $batchExamStudentRemap->getMapping($oldId);
            $row['exam_group_class_batch_exam_id'] = $batchExamRemap->getMapping((int) $row['exam_group_class_batch_exam_id']);
            $row['student_id'] = $studentMap[$oldStudentId];
            $row['student_session_id'] = $studentSessionMap[$oldStudentSessionId];
            $batchExamStudentRowsToInsert[$oldId] = $row;
        }

        $resultRemap = new IdRemapper($this->nextId('exam_group_exam_results'));
        $results = $this->fetchAll(
            'SELECT id, exam_group_class_batch_exam_student_id, exam_group_class_batch_exam_subject_id, attendence,'
            . ' get_marks, note, is_active, created_at, updated_at FROM exam_group_exam_results'
        );
        $resultSourceTotal = count($results);
        $resultSkipped = 0;
        $resultRowsToInsert = [];
        foreach ($results as $row) {
            $oldStudentLinkId = (int) $row['exam_group_class_batch_exam_student_id'];
            if (!isset($batchExamStudentRowsToInsert[$oldStudentLinkId])) {
                $resultSkipped++;
                continue;
            }
            $row['exam_group_class_batch_exam_student_id'] = $batchExamStudentRemap->getMapping($oldStudentLinkId);
            if ($row['exam_group_class_batch_exam_subject_id'] !== null) {
                $row['exam_group_class_batch_exam_subject_id'] = $batchExamSubjectRemap->getMapping((int) $row['exam_group_class_batch_exam_subject_id']);
            }
            $resultRowsToInsert[] = $row;
        }

        $this->inTransaction(function () use (
            $sessions, $subjects, $examGroups, $batchExams, $batchExamSubjects,
            $sessionRemap, $subjectRemap, $examGroupRemap, $batchExamRemap, $batchExamSubjectRemap,
            $batchExamStudentRowsToInsert, $resultRowsToInsert
        ) {
            foreach ($sessions as $row) {
                $row['id'] = $sessionRemap->getMapping((int) $row['id']);
                $this->insertRow('sessions', $row);
            }
            foreach ($subjects as $row) {
                $row['id'] = $subjectRemap->getMapping((int) $row['id']);
                $this->insertRow('subjects', $row);
            }
            foreach ($examGroups as $row) {
                $row['id'] = $examGroupRemap->getMapping((int) $row['id']);
                $this->insertRow('exam_groups', $row);
            }
            foreach ($batchExams as $row) {
                $oldId = (int) $row['id'];
                $row['id'] = $batchExamRemap->getMapping($oldId);
                $row['session_id'] = $sessionRemap->getMapping((int) $row['session_id']);
                $row['exam_group_id'] = $examGroupRemap->getMapping((int) $row['exam_group_id']);
                $this->insertRow('exam_group_class_batch_exams', $row);
            }
            foreach ($batchExamSubjects as $row) {
                $oldId = (int) $row['id'];
                $row['id'] = $batchExamSubjectRemap->getMapping($oldId);
                $row['exam_group_class_batch_exams_id'] = $batchExamRemap->getMapping((int) $row['exam_group_class_batch_exams_id']);
                $row['subject_id'] = $subjectRemap->getMapping((int) $row['subject_id']);
                $this->insertRow('exam_group_class_batch_exam_subjects', $row);
            }
            foreach ($batchExamStudentRowsToInsert as $row) {
                $this->insertRow('exam_group_class_batch_exam_students', $row);
            }
            foreach ($resultRowsToInsert as $row) {
                $this->insertRow('exam_group_exam_results', $row);
            }
        });

        return [
            'sessions_migrated' => count($sessions),
            'subjects_migrated' => count($subjects),
            'exam_groups_migrated' => count($examGroups),
            'exam_group_class_batch_exams_migrated' => count($batchExams),
            'exam_group_class_batch_exam_subjects_migrated' => count($batchExamSubjects),
            'exam_group_class_batch_exam_students_migrated' => count($batchExamStudentRowsToInsert),
            'exam_group_class_batch_exam_students_source_total' => $batchExamStudentSourceTotal,
            'exam_group_class_batch_exam_students_skipped' => $batchExamStudentSkipped,
            'exam_group_exam_results_migrated' => count($resultRowsToInsert),
            'exam_group_exam_results_source_total' => $resultSourceTotal,
            'exam_group_exam_results_skipped' => $resultSkipped,
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeExamData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeExamData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['sessions_migrated']} sessions, {$result['subjects_migrated']} subjects,"
        . " {$result['exam_groups_migrated']} exam groups, {$result['exam_group_class_batch_exams_migrated']} batch exams,"
        . " {$result['exam_group_class_batch_exam_subjects_migrated']} exam subjects,"
        . " {$result['exam_group_class_batch_exam_students_migrated']} exam-student enrollments, and"
        . " {$result['exam_group_exam_results_migrated']} exam results for tenant {$tenantId}.\n";

    if ($result['exam_group_class_batch_exam_students_skipped'] > 0) {
        fwrite(
            STDERR,
            "WARNING: {$result['exam_group_class_batch_exam_students_skipped']} of"
            . " {$result['exam_group_class_batch_exam_students_source_total']} exam-student enrollments could not be"
            . " resolved and were skipped. Investigate before trusting this migration.\n"
        );
    }
    if ($result['exam_group_exam_results_skipped'] > 0) {
        fwrite(
            STDERR,
            "WARNING: {$result['exam_group_exam_results_skipped']} of {$result['exam_group_exam_results_source_total']}"
            . " exam results could not be resolved and were skipped. Investigate before trusting this migration.\n"
        );
    }
}
