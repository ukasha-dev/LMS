<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/NaturalKeyIdResolver.php';
require_once __DIR__ . '/ClassSectionPairResolver.php';

final class MergeStudentSessionData extends AbstractTenantMerger
{
    public function run(): array
    {
        $this->guardAgainstExistingData('student_session');

        $studentResolver = new NaturalKeyIdResolver();
        $studentMap = $studentResolver->resolve($this->source, $this->target, $this->tenantId, 'students', 'admission_no');

        $pairResolver = new ClassSectionPairResolver();
        $classSectionMap = $pairResolver->resolve($this->source, $this->target, $this->tenantId);

        $sourceRows = $this->fetchAll(
            'SELECT student_id, class_id, section_id, is_active, created_at, updated_at FROM student_session'
        );

        $rowsToInsert = [];
        foreach ($sourceRows as $row) {
            $oldStudentId = (int) $row['student_id'];
            $oldPairKey = $row['class_id'] . ':' . $row['section_id'];
            if (!isset($studentMap[$oldStudentId]) || !isset($classSectionMap[$oldPairKey])) {
                continue;
            }
            $row['student_id'] = $studentMap[$oldStudentId];
            $row['class_id'] = $classSectionMap[$oldPairKey]['class_id'];
            $row['section_id'] = $classSectionMap[$oldPairKey]['section_id'];
            $rowsToInsert[] = $row;
        }

        $this->inTransaction(function () use ($rowsToInsert) {
            foreach ($rowsToInsert as $row) {
                $this->insertRow('student_session', $row);
            }
        });

        return ['student_session_migrated' => count($rowsToInsert)];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeStudentSessionData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeStudentSessionData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['student_session_migrated']} student_session rows for tenant {$tenantId}.\n";
}
