<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';

final class MergeClassData extends AbstractTenantMerger
{
    public function run(): array
    {
        $this->guardAgainstExistingData('classes', 'sections', 'class_sections');

        $classRemap = new IdRemapper($this->nextId('classes'));
        $sectionRemap = new IdRemapper($this->nextId('sections'));

        $classes = $this->fetchAll('SELECT id, class, is_active, created_at, updated_at FROM classes');
        $sections = $this->fetchAll('SELECT id, section, is_active, created_at, updated_at FROM sections');
        $classSections = $this->fetchAll('SELECT class_id, section_id, is_active, created_at, updated_at FROM class_sections');

        foreach ($classes as $row) {
            $classRemap->remapId((int) $row['id']);
        }
        foreach ($sections as $row) {
            $sectionRemap->remapId((int) $row['id']);
        }

        $this->inTransaction(function () use ($classes, $sections, $classSections, $classRemap, $sectionRemap) {
            foreach ($classes as $row) {
                $row['id'] = $classRemap->getMapping((int) $row['id']);
                $this->insertRow('classes', $row);
            }
            foreach ($sections as $row) {
                $row['id'] = $sectionRemap->getMapping((int) $row['id']);
                $this->insertRow('sections', $row);
            }
            foreach ($classSections as $row) {
                if (!$classRemap->hasMapping((int) $row['class_id']) || !$sectionRemap->hasMapping((int) $row['section_id'])) {
                    continue;
                }
                $row['class_id'] = $classRemap->getMapping((int) $row['class_id']);
                $row['section_id'] = $sectionRemap->getMapping((int) $row['section_id']);
                $this->insertRow('class_sections', $row);
            }
        });

        return [
            'classes_migrated' => count($classes),
            'sections_migrated' => count($sections),
            'class_sections_migrated' => count($classSections),
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeClassData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeClassData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['classes_migrated']} classes, {$result['sections_migrated']} sections, {$result['class_sections_migrated']} class_sections for tenant {$tenantId}.\n";
}
