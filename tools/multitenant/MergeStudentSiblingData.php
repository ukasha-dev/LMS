<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';
require_once __DIR__ . '/NaturalKeyIdResolver.php';

final class MergeStudentSiblingData extends AbstractTenantMerger
{
    public function run(): array
    {
        $this->guardAgainstExistingData('student_sibling');

        $studentResolver = new NaturalKeyIdResolver();
        $studentMap = $studentResolver->resolve($this->source, $this->target, $this->tenantId, 'students', 'admission_no');

        $siblings = $this->fetchAll('SELECT id, student_id, sibling_id FROM student_sibling');
        $sourceTotal = count($siblings);
        $skipped = 0;

        $remap = new IdRemapper($this->nextId('student_sibling'));
        $rowsToInsert = [];
        foreach ($siblings as $row) {
            $oldStudentId = (int) $row['student_id'];
            $oldSiblingId = (int) $row['sibling_id'];
            if (!isset($studentMap[$oldStudentId]) || !isset($studentMap[$oldSiblingId])) {
                $skipped++;
                continue;
            }
            $oldId = (int) $row['id'];
            $remap->remapId($oldId);
            $row['id'] = $remap->getMapping($oldId);
            $row['student_id'] = $studentMap[$oldStudentId];
            $row['sibling_id'] = $studentMap[$oldSiblingId];
            $rowsToInsert[] = $row;
        }

        $this->inTransaction(function () use ($rowsToInsert) {
            foreach ($rowsToInsert as $row) {
                $this->insertRow('student_sibling', $row);
            }
        });

        return [
            'student_sibling_migrated' => count($rowsToInsert),
            'student_sibling_source_total' => $sourceTotal,
            'student_sibling_skipped' => $skipped,
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeStudentSiblingData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeStudentSiblingData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['student_sibling_migrated']} student sibling links for tenant {$tenantId}.\n";

    if ($result['student_sibling_skipped'] > 0) {
        fwrite(
            STDERR,
            "WARNING: {$result['student_sibling_skipped']} of {$result['student_sibling_source_total']} sibling links"
            . " could not be resolved and were skipped. Investigate before trusting this migration.\n"
        );
    }
}
