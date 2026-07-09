<?php

require_once __DIR__ . '/IdRemapper.php';

final class MergeClassData
{
    private PDO $source;
    private PDO $target;
    private int $tenantId;

    public function __construct(PDO $source, PDO $target, int $tenantId)
    {
        $this->source = $source;
        $this->target = $target;
        $this->tenantId = $tenantId;
    }

    public function run(): array
    {
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

        $this->target->beginTransaction();
        try {
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
            $this->target->commit();
        } catch (Throwable $e) {
            $this->target->rollBack();
            throw $e;
        }

        return [
            'classes_migrated' => count($classes),
            'sections_migrated' => count($sections),
            'class_sections_migrated' => count($classSections),
        ];
    }

    private function nextId(string $table): int
    {
        $stmt = $this->target->query("SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM `{$table}`");

        return (int) $stmt->fetch(PDO::FETCH_ASSOC)['next_id'];
    }

    private function fetchAll(string $sql): array
    {
        return $this->source->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    private function insertRow(string $table, array $row): void
    {
        $row['tenant_id'] = $this->tenantId;
        $columns = array_keys($row);
        $placeholders = array_map(static fn ($c) => ':' . $c, $columns);

        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->target->prepare($sql);

        $params = [];
        foreach ($row as $column => $value) {
            $params[':' . $column] = $value;
        }
        $stmt->execute($params);
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
