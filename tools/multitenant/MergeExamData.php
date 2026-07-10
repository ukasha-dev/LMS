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

        $this->inTransaction(function () use (
            $sessions, $subjects, $examGroups, $batchExams, $batchExamSubjects,
            $sessionRemap, $subjectRemap, $examGroupRemap, $batchExamRemap, $batchExamSubjectRemap
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
        });

        return [
            'sessions_migrated' => count($sessions),
            'subjects_migrated' => count($subjects),
            'exam_groups_migrated' => count($examGroups),
            'exam_group_class_batch_exams_migrated' => count($batchExams),
            'exam_group_class_batch_exam_subjects_migrated' => count($batchExamSubjects),
        ];
    }
}
