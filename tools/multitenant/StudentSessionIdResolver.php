<?php

final class StudentSessionIdResolver
{
    public function resolve(PDO $source, PDO $target, int $tenantId): array
    {
        $sourceRows = $source->query(
            'SELECT student_session.id AS id, students.admission_no AS admission_no,'
            . ' classes.class AS class_name, sections.section AS section_name'
            . ' FROM student_session'
            . ' JOIN students ON students.id = student_session.student_id'
            . ' JOIN classes ON classes.id = student_session.class_id'
            . ' JOIN sections ON sections.id = student_session.section_id'
            . " WHERE student_session.is_active = 'yes'"
        )->fetchAll(PDO::FETCH_ASSOC);

        $targetStmt = $target->prepare(
            'SELECT student_session.id AS id, students.admission_no AS admission_no,'
            . ' classes.class AS class_name, sections.section AS section_name'
            . ' FROM student_session'
            . ' JOIN students ON students.id = student_session.student_id'
            . ' JOIN classes ON classes.id = student_session.class_id'
            . ' JOIN sections ON sections.id = student_session.section_id'
            . " WHERE student_session.tenant_id = :tenant_id AND student_session.is_active = 'yes'"
        );
        $targetStmt->execute([':tenant_id' => $tenantId]);
        $targetRows = $targetStmt->fetchAll(PDO::FETCH_ASSOC);

        $sourceMap = $this->buildKeyedMap($sourceRows, 'source');
        $targetMap = $this->buildKeyedMap($targetRows, 'target');

        $oldToNew = [];
        foreach ($sourceMap as $key => $oldId) {
            if (isset($targetMap[$key])) {
                $oldToNew[$oldId] = $targetMap[$key];
            }
        }

        return $oldToNew;
    }

    private function buildKeyedMap(array $rows, string $side): array
    {
        $map = [];
        foreach ($rows as $row) {
            $key = $row['admission_no'] . "\x00" . $row['class_name'] . "\x00" . $row['section_name'];
            $id = (int) $row['id'];
            if (isset($map[$key]) && $map[$key] !== $id) {
                throw new RuntimeException(
                    "Ambiguous student_session key: multiple distinct ids share"
                    . " admission_no/class/section \"{$row['admission_no']}\"/\"{$row['class_name']}\"/\"{$row['section_name']}\""
                    . " in {$side} data — cannot safely resolve. Manual investigation required."
                );
            }
            $map[$key] = $id;
        }

        return $map;
    }
}
