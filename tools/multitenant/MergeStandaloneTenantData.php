<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';

// Generic tool for TENANT-SCOPED tables that have no foreign key
// dependency on any other non-global table -- config/reference tables
// like holiday_type, categories, item_store, etc. Columns are read from
// information_schema, not hardcoded, the same way MergeGlobalReferenceData
// avoids a hardcoded column list. Unlike MergeGlobalReferenceData, ids ARE
// remapped (via IdRemapper) since this table is shared across every tenant
// in the same physical table, not copied once. Do not use this for a table
// that has any column referencing another tenant-scoped table's id (e.g.
// student_id, staff_id, session_id) -- those need a real Merge*Data tool
// with proper natural-key resolution against already-migrated data.
final class MergeStandaloneTenantData extends AbstractTenantMerger
{
    public function __construct(PDO $source, PDO $target, int $tenantId, private string $table)
    {
        parent::__construct($source, $target, $tenantId);
    }

    public function run(): array
    {
        $this->guardAgainstExistingData($this->table);

        $columns = $this->source->query(
            "SELECT column_name FROM information_schema.columns "
            . "WHERE table_schema = DATABASE() AND table_name = '{$this->table}' "
            . "AND column_name != 'tenant_id' ORDER BY ordinal_position"
        )->fetchAll(PDO::FETCH_COLUMN);

        $rows = $this->fetchAll('SELECT * FROM `' . $this->table . '`');

        $remap = new IdRemapper($this->nextId($this->table));
        foreach ($rows as $row) {
            $remap->remapId((int) $row['id']);
        }

        $this->inTransaction(function () use ($rows, $columns, $remap) {
            foreach ($rows as $row) {
                $newRow = [];
                foreach ($columns as $column) {
                    $newRow[$column] = $row[$column];
                }
                $newRow['id'] = $remap->getMapping((int) $row['id']);
                $this->insertRow($this->table, $newRow);
            }
        });

        return ['migrated' => count($rows)];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $table = $argv[2] ?? null;
    $tenantId = isset($argv[3]) ? (int) $argv[3] : null;

    if (!$sourceDb || !$table || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeStandaloneTenantData.php <source_database_name> <table_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeStandaloneTenantData($source, $target, $tenantId, $table);
    $result = $merger->run();

    echo "Migrated {$result['migrated']} rows into `{$table}` for tenant {$tenantId}.\n";
}
