<?php

final class ClassSectionPairResolver
{
    public function resolve(PDO $source, PDO $target, int $tenantId): array
    {
        $sourceRows = $source->query(
            'SELECT class_sections.class_id AS class_id, class_sections.section_id AS section_id,'
            . ' classes.class AS class_name, sections.section AS section_name'
            . ' FROM class_sections'
            . ' JOIN classes ON classes.id = class_sections.class_id'
            . ' JOIN sections ON sections.id = class_sections.section_id'
        )->fetchAll(PDO::FETCH_ASSOC);

        $targetStmt = $target->prepare(
            'SELECT class_sections.class_id AS class_id, class_sections.section_id AS section_id,'
            . ' classes.class AS class_name, sections.section AS section_name'
            . ' FROM class_sections'
            . ' JOIN classes ON classes.id = class_sections.class_id'
            . ' JOIN sections ON sections.id = class_sections.section_id'
            . ' WHERE class_sections.tenant_id = :tenant_id'
        );
        $targetStmt->execute([':tenant_id' => $tenantId]);
        $targetRows = $targetStmt->fetchAll(PDO::FETCH_ASSOC);

        $targetByNamePair = [];
        foreach ($targetRows as $row) {
            $namePairKey = $row['class_name'] . "\x00" . $row['section_name'];
            $candidate = [
                'class_id' => (int) $row['class_id'],
                'section_id' => (int) $row['section_id'],
            ];
            if (isset($targetByNamePair[$namePairKey]) && $targetByNamePair[$namePairKey] !== $candidate) {
                throw new RuntimeException(
                    "Ambiguous class/section pair: multiple distinct (class_id, section_id) combinations share the name pair \"{$row['class_name']}\" / \"{$row['section_name']}\" — cannot safely resolve. Manual investigation required."
                );
            }
            $targetByNamePair[$namePairKey] = $candidate;
        }

        $sourceByNamePair = [];
        foreach ($sourceRows as $row) {
            $namePairKey = $row['class_name'] . "\x00" . $row['section_name'];
            $candidate = [
                'class_id' => (int) $row['class_id'],
                'section_id' => (int) $row['section_id'],
            ];
            if (isset($sourceByNamePair[$namePairKey]) && $sourceByNamePair[$namePairKey] !== $candidate) {
                throw new RuntimeException(
                    "Ambiguous class/section pair: multiple distinct (class_id, section_id) combinations share the name pair \"{$row['class_name']}\" / \"{$row['section_name']}\" — cannot safely resolve. Manual investigation required."
                );
            }
            $sourceByNamePair[$namePairKey] = $candidate;
        }

        $map = [];
        foreach ($sourceRows as $row) {
            $namePairKey = $row['class_name'] . "\x00" . $row['section_name'];
            if (!isset($targetByNamePair[$namePairKey])) {
                continue;
            }
            $oldPairKey = $row['class_id'] . ':' . $row['section_id'];
            $map[$oldPairKey] = $targetByNamePair[$namePairKey];
        }

        return $map;
    }
}
