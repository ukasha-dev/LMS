<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';

final class MergeStaffData extends AbstractTenantMerger
{
    public function run(): array
    {
        $staffRemap = new IdRemapper($this->nextId('staff'));
        $roleRemap = new IdRemapper($this->nextId('roles'));

        $staff = $this->fetchAll(
            'SELECT id, employee_id, name, surname, email, password, gender, image, is_active, verification_code, lang_id, currency_id, created_at, updated_at FROM staff'
        );
        $roles = $this->fetchAll(
            'SELECT id, name, slug, is_active, is_system, is_superadmin, created_at, updated_at FROM roles'
        );
        $staffRoles = $this->fetchAll('SELECT staff_id, role_id, is_active, created_at, updated_at FROM staff_roles');

        foreach ($staff as $row) {
            $staffRemap->remapId((int) $row['id']);
        }
        foreach ($roles as $row) {
            $roleRemap->remapId((int) $row['id']);
        }

        $this->inTransaction(function () use ($staff, $roles, $staffRoles, $staffRemap, $roleRemap) {
            foreach ($staff as $row) {
                $row['id'] = $staffRemap->getMapping((int) $row['id']);
                $this->insertRow('staff', $row);
            }
            foreach ($roles as $row) {
                $row['id'] = $roleRemap->getMapping((int) $row['id']);
                $this->insertRow('roles', $row);
            }
            foreach ($staffRoles as $row) {
                if (!$staffRemap->hasMapping((int) $row['staff_id']) || !$roleRemap->hasMapping((int) $row['role_id'])) {
                    continue;
                }
                $row['staff_id'] = $staffRemap->getMapping((int) $row['staff_id']);
                $row['role_id'] = $roleRemap->getMapping((int) $row['role_id']);
                $this->insertRow('staff_roles', $row);
            }
        });

        return [
            'staff_migrated' => count($staff),
            'roles_migrated' => count($roles),
            'staff_roles_migrated' => count($staffRoles),
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeStaffData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeStaffData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['staff_migrated']} staff, {$result['roles_migrated']} roles, {$result['staff_roles_migrated']} staff_roles for tenant {$tenantId}.\n";
}
