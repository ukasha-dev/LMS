<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';

final class MergeGradeData extends AbstractTenantMerger
{
    public function run(): array
    {
        $this->guardAgainstExistingData('grades');

        $gradeRemap = new IdRemapper($this->nextId('grades'));

        $grades = $this->fetchAll(
            'SELECT id, exam_type, name, point, mark_from, mark_upto, description, is_active FROM grades'
        );

        foreach ($grades as $row) {
            $gradeRemap->remapId((int) $row['id']);
        }

        $this->inTransaction(function () use ($grades, $gradeRemap) {
            foreach ($grades as $row) {
                $row['id'] = $gradeRemap->getMapping((int) $row['id']);
                $this->insertRow('grades', $row);
            }
        });

        return [
            'grades_migrated' => count($grades),
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeGradeData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeGradeData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['grades_migrated']} grades for tenant {$tenantId}.\n";
}
