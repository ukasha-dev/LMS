<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';
require_once __DIR__ . '/NaturalKeyIdResolver.php';

final class MergeHrData extends AbstractTenantMerger
{
    public function run(): array
    {
        $departmentRemap = new IdRemapper($this->nextId('department'));
        $departments = $this->fetchAll('SELECT id, department_name, is_active, created_at, updated_at FROM department');
        foreach ($departments as $row) {
            $departmentRemap->remapId((int) $row['id']);
        }

        $designationRemap = new IdRemapper($this->nextId('staff_designation'));
        $designations = $this->fetchAll('SELECT id, designation, is_active, created_at, updated_at FROM staff_designation');
        foreach ($designations as $row) {
            $designationRemap->remapId((int) $row['id']);
        }

        $leaveTypeRemap = new IdRemapper($this->nextId('leave_types'));
        $leaveTypes = $this->fetchAll('SELECT id, type, is_active FROM leave_types');
        foreach ($leaveTypes as $row) {
            $leaveTypeRemap->remapId((int) $row['id']);
        }

        $staffResolver = new NaturalKeyIdResolver();
        $staffMap = $staffResolver->resolve($this->source, $this->target, $this->tenantId, 'staff', 'email');

        $sldRemap = new IdRemapper($this->nextId('staff_leave_details'));
        $sldRows = $this->fetchAll(
            'SELECT id, staff_id, leave_type_id, alloted_leave, created_at, updated_at FROM staff_leave_details'
        );
        $sldSourceTotal = count($sldRows);
        $sldSkipped = 0;
        $sldRowsToInsert = [];
        foreach ($sldRows as $row) {
            $oldId = (int) $row['id'];
            $oldStaffId = (int) $row['staff_id'];
            $oldLeaveTypeId = (int) $row['leave_type_id'];
            if (!isset($staffMap[$oldStaffId]) || !$leaveTypeRemap->hasMapping($oldLeaveTypeId)) {
                $sldSkipped++;
                continue;
            }
            $sldRemap->remapId($oldId);
            $row['id'] = $sldRemap->getMapping($oldId);
            $row['staff_id'] = $staffMap[$oldStaffId];
            $row['leave_type_id'] = $leaveTypeRemap->getMapping($oldLeaveTypeId);
            $sldRowsToInsert[$oldId] = $row;
        }

        $this->inTransaction(function () use (
            $departments, $designations, $leaveTypes, $sldRowsToInsert,
            $departmentRemap, $designationRemap, $leaveTypeRemap
        ) {
            foreach ($departments as $row) {
                $row['id'] = $departmentRemap->getMapping((int) $row['id']);
                $this->insertRow('department', $row);
            }
            foreach ($designations as $row) {
                $row['id'] = $designationRemap->getMapping((int) $row['id']);
                $this->insertRow('staff_designation', $row);
            }
            foreach ($leaveTypes as $row) {
                $row['id'] = $leaveTypeRemap->getMapping((int) $row['id']);
                $this->insertRow('leave_types', $row);
            }
            foreach ($sldRowsToInsert as $row) {
                $this->insertRow('staff_leave_details', $row);
            }
        });

        return [
            'department_migrated' => count($departments),
            'staff_designation_migrated' => count($designations),
            'leave_types_migrated' => count($leaveTypes),
            'staff_leave_details_migrated' => count($sldRowsToInsert),
            'staff_leave_details_source_total' => $sldSourceTotal,
            'staff_leave_details_skipped' => $sldSkipped,
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeHrData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeHrData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['department_migrated']} departments, {$result['staff_designation_migrated']} designations,"
        . " {$result['leave_types_migrated']} leave types, and {$result['staff_leave_details_migrated']} staff leave allotments"
        . " for tenant {$tenantId}.\n";

    if ($result['staff_leave_details_skipped'] > 0) {
        fwrite(
            STDERR,
            "WARNING: {$result['staff_leave_details_skipped']} of {$result['staff_leave_details_source_total']}"
            . " staff leave allotments could not be resolved and were skipped."
            . " Investigate before trusting this migration.\n"
        );
    }
}
