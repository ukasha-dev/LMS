<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';
require_once __DIR__ . '/NaturalKeyIdResolver.php';

final class MergeStudentDocData extends AbstractTenantMerger
{
    public function run(): array
    {
        $this->guardAgainstExistingData('student_doc');

        $studentResolver = new NaturalKeyIdResolver();
        $studentMap = $studentResolver->resolve($this->source, $this->target, $this->tenantId, 'students', 'admission_no');

        $docs = $this->fetchAll('SELECT id, student_id, title, doc, created_at, updated_at FROM student_doc');
        $sourceTotal = count($docs);
        $skipped = 0;

        $remap = new IdRemapper($this->nextId('student_doc'));
        $rowsToInsert = [];
        foreach ($docs as $row) {
            $oldStudentId = (int) $row['student_id'];
            if (!isset($studentMap[$oldStudentId])) {
                $skipped++;
                continue;
            }
            $oldId = (int) $row['id'];
            $remap->remapId($oldId);
            $row['id'] = $remap->getMapping($oldId);
            $row['student_id'] = $studentMap[$oldStudentId];
            $rowsToInsert[] = $row;
        }

        $this->inTransaction(function () use ($rowsToInsert) {
            foreach ($rowsToInsert as $row) {
                $this->insertRow('student_doc', $row);
            }
        });

        return [
            'student_doc_migrated' => count($rowsToInsert),
            'student_doc_source_total' => $sourceTotal,
            'student_doc_skipped' => $skipped,
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeStudentDocData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeStudentDocData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['student_doc_migrated']} student documents for tenant {$tenantId}.\n";

    if ($result['student_doc_skipped'] > 0) {
        fwrite(
            STDERR,
            "WARNING: {$result['student_doc_skipped']} of {$result['student_doc_source_total']} student documents"
            . " could not be resolved and were skipped. Investigate before trusting this migration.\n"
        );
    }
}
