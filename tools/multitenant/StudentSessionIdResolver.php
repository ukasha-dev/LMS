<?php

final class StudentSessionIdResolver
{
    public function resolve(PDO $source, PDO $target, int $tenantId): array
    {
        $sourceRows = $source->query(
            'SELECT student_session.id AS id, students.admission_no AS admission_no,'
            . ' classes.class AS class_name, sections.section AS section_name,'
            . ' student_session.created_at AS created_at, students.is_active AS student_is_active'
            . ' FROM student_session'
            . ' JOIN students ON students.id = student_session.student_id'
            . ' JOIN classes ON classes.id = student_session.class_id'
            . ' JOIN sections ON sections.id = student_session.section_id'
        )->fetchAll(PDO::FETCH_ASSOC);

        $targetStmt = $target->prepare(
            'SELECT student_session.id AS id, students.admission_no AS admission_no,'
            . ' classes.class AS class_name, sections.section AS section_name,'
            . ' student_session.created_at AS created_at, students.is_active AS student_is_active'
            . ' FROM student_session'
            . ' JOIN students ON students.id = student_session.student_id'
            . ' JOIN classes ON classes.id = student_session.class_id'
            . ' JOIN sections ON sections.id = student_session.section_id'
            . ' WHERE student_session.tenant_id = :tenant_id'
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
        $candidatesByKey = [];
        foreach ($rows as $row) {
            $key = $row['admission_no'] . "\x00" . $row['class_name'] . "\x00" . $row['section_name'] . "\x00" . $row['created_at'];
            $candidatesByKey[$key][] = [
                'id' => (int) $row['id'],
                'active' => $this->isActiveValue($row['student_is_active']),
                'row' => $row,
            ];
        }

        $map = [];
        foreach ($candidatesByKey as $key => $candidates) {
            $distinctIds = array_values(array_unique(array_column($candidates, 'id')));
            if (count($distinctIds) === 1) {
                $map[$key] = $distinctIds[0];
                continue;
            }

            // A duplicate admission_no on the underlying student (see
            // NaturalKeyIdResolver) can produce two session rows that
            // collide on this exact composite key when both duplicates got
            // bulk-assigned to the same class/section at the same batch
            // timestamp. Same tiebreak as NaturalKeyIdResolver: prefer the
            // one active student's session row; drop the key if none of the
            // colliding students are active (nothing live to attribute it
            // to); only throw when genuinely ambiguous (multiple active).
            $activeIds = array_values(array_unique(array_column(
                array_filter($candidates, static fn (array $c): bool => $c['active'] === true),
                'id'
            )));
            if (count($activeIds) === 1) {
                $map[$key] = $activeIds[0];
                continue;
            }
            if (count($activeIds) === 0) {
                continue;
            }

            $row = $candidates[0]['row'];
            throw new RuntimeException(
                "Ambiguous student_session key: multiple distinct ids share"
                . " admission_no/class/section/created_at \"{$row['admission_no']}\"/\"{$row['class_name']}\"/\"{$row['section_name']}\"/\"{$row['created_at']}\""
                . " in {$side} data — cannot safely resolve. Manual investigation required."
            );
        }

        return $map;
    }

    private function isActiveValue($value): bool
    {
        if (is_string($value)) {
            return strtolower(trim($value)) === 'yes' || trim($value) === '1';
        }

        return ((int) $value) === 1;
    }
}
