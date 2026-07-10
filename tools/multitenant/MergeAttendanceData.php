<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';
require_once __DIR__ . '/StudentSessionIdResolver.php';

final class MergeAttendanceData extends AbstractTenantMerger
{
    public function run(): array
    {
        $typeRemap = new IdRemapper($this->nextId('attendence_type'));

        $types = $this->fetchAll(
            'SELECT id, type, key_value, long_lang_name, long_name_style, is_active, for_qr_attendance, for_schedule, created_at, updated_at FROM attendence_type'
        );
        foreach ($types as $row) {
            $typeRemap->remapId((int) $row['id']);
        }

        $sessionResolver = new StudentSessionIdResolver();
        $sessionMap = $sessionResolver->resolve($this->source, $this->target, $this->tenantId);

        $attendances = $this->fetchAll(
            'SELECT student_session_id, date, attendence_type_id, remark, is_active, in_time, out_time, created_at, updated_at FROM student_attendences'
        );

        $rowsToInsert = [];
        foreach ($attendances as $row) {
            $oldSessionId = (int) $row['student_session_id'];
            $oldTypeId = (int) $row['attendence_type_id'];
            if (!isset($sessionMap[$oldSessionId]) || !$typeRemap->hasMapping($oldTypeId)) {
                continue;
            }
            $row['student_session_id'] = $sessionMap[$oldSessionId];
            $row['attendence_type_id'] = $typeRemap->getMapping($oldTypeId);
            $rowsToInsert[] = $row;
        }

        $this->inTransaction(function () use ($types, $typeRemap, $rowsToInsert) {
            foreach ($types as $row) {
                $row['id'] = $typeRemap->getMapping((int) $row['id']);
                $this->insertRow('attendence_type', $row);
            }
            foreach ($rowsToInsert as $row) {
                $this->insertRow('student_attendences', $row);
            }
        });

        return [
            'attendence_types_migrated' => count($types),
            'student_attendences_migrated' => count($rowsToInsert),
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeAttendanceData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeAttendanceData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['attendence_types_migrated']} attendance types and {$result['student_attendences_migrated']} student attendance records for tenant {$tenantId}.\n";
}
