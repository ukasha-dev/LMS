<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';

final class MergePermissionCategoryData extends AbstractTenantMerger
{
    public function run(): array
    {
        $this->guardAgainstExistingData('permission_category');

        $categoryRemap = new IdRemapper($this->nextId('permission_category'));

        // perm_group_id references the GLOBAL permission_group table, which
        // MergePermissionGroupData copies with source ids preserved -- no
        // remapping needed for that reference, unlike every tenant-scoped
        // foreign key elsewhere in this project.
        $categories = $this->fetchAll(
            'SELECT id, perm_group_id, name, short_code, enable_view, enable_add, enable_edit, enable_delete '
            . 'FROM permission_category'
        );

        foreach ($categories as $row) {
            $categoryRemap->remapId((int) $row['id']);
        }

        $this->inTransaction(function () use ($categories, $categoryRemap) {
            foreach ($categories as $row) {
                $row['id'] = $categoryRemap->getMapping((int) $row['id']);
                $this->insertRow('permission_category', $row);
            }
        });

        return [
            'permission_category_migrated' => count($categories),
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergePermissionCategoryData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergePermissionCategoryData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['permission_category_migrated']} permission_category rows for tenant {$tenantId}.\n";
}
