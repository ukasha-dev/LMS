<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';
require_once __DIR__ . '/NaturalKeyIdResolver.php';

final class MergeRolesPermissionsData extends AbstractTenantMerger
{
    public function run(): array
    {
        $this->guardAgainstExistingData('roles_permissions');

        $resolver = new NaturalKeyIdResolver();
        $roleMap = $resolver->resolve($this->source, $this->target, $this->tenantId, 'roles', 'name');
        $categoryMap = $resolver->resolve($this->source, $this->target, $this->tenantId, 'permission_category', 'short_code');

        $rowsPermRemap = new IdRemapper($this->nextId('roles_permissions'));

        $rows = $this->fetchAll(
            'SELECT id, role_id, perm_cat_id, can_view, can_add, can_edit, can_delete FROM roles_permissions'
        );

        $migrate = [];
        $skipped = 0;
        foreach ($rows as $row) {
            $sourceRoleId = (int) $row['role_id'];
            $sourceCategoryId = (int) $row['perm_cat_id'];

            if (!isset($roleMap[$sourceRoleId]) || !isset($categoryMap[$sourceCategoryId])) {
                $skipped++;
                continue;
            }

            $row['role_id'] = $roleMap[$sourceRoleId];
            $row['perm_cat_id'] = $categoryMap[$sourceCategoryId];
            $migrate[] = $row;
        }

        foreach ($migrate as $row) {
            $rowsPermRemap->remapId((int) $row['id']);
        }

        $this->inTransaction(function () use ($migrate, $rowsPermRemap) {
            foreach ($migrate as $row) {
                $row['id'] = $rowsPermRemap->getMapping((int) $row['id']);
                $this->insertRow('roles_permissions', $row);
            }
        });

        if ($skipped > 0) {
            fwrite(STDERR, "Warning: skipped {$skipped} roles_permissions row(s) with unresolvable role_id/perm_cat_id.\n");
        }

        return [
            'roles_permissions_migrated' => count($migrate),
            'roles_permissions_skipped' => $skipped,
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeRolesPermissionsData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeRolesPermissionsData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['roles_permissions_migrated']} roles_permissions rows for tenant {$tenantId} "
        . "({$result['roles_permissions_skipped']} skipped).\n";
}
