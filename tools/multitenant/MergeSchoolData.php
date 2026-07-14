<?php

require_once __DIR__ . '/AbstractTenantMerger.php';
require_once __DIR__ . '/IdRemapper.php';

final class MergeSchoolData extends AbstractTenantMerger
{
    public function run(): array
    {
        $this->guardAgainstExistingData('students', 'users');

        $studentRemap = new IdRemapper($this->nextId('students'));
        $userRemap = new IdRemapper($this->nextId('users'));

        // Explicit column lists, not SELECT *: the source school databases
        // have the full production schema (~55 columns on students alone,
        // including a `users.childs` column encoding parent->children as a
        // CSV of student ids). school_saas's students/users tables are a
        // deliberately minimal Phase 1 subset (see Task 4). Selecting only
        // the columns that subset actually has keeps this tool honest about
        // what it migrates — anything not listed here (childs included) is
        // explicitly deferred to Phase 2's full-parity schema, not silently
        // dropped or, worse, inserted unremapped into a column that doesn't
        // encode the same ids anymore.
        $students = $this->fetchAll(
            'SELECT id, parent_id, admission_no, firstname, middlename, lastname, is_active FROM students'
        );
        $users = $this->fetchAll(
            'SELECT id, user_id, username, password, role, is_active, created_at FROM users'
        );

        foreach ($students as $row) {
            $studentRemap->remapId((int) $row['id']);
        }
        foreach ($users as $row) {
            $userRemap->remapId((int) $row['id']);
        }

        $this->inTransaction(function () use ($students, $users, $studentRemap, $userRemap) {
            foreach ($students as $row) {
                $row['id'] = $studentRemap->getMapping((int) $row['id']);
                $row['parent_id'] = $userRemap->hasMapping((int) $row['parent_id'])
                    ? $userRemap->getMapping((int) $row['parent_id'])
                    : 0;
                $this->insertRow('students', $row);
            }
            foreach ($users as $row) {
                $row['id'] = $userRemap->getMapping((int) $row['id']);
                $row['user_id'] = $studentRemap->hasMapping((int) $row['user_id'])
                    ? $studentRemap->getMapping((int) $row['user_id'])
                    : 0;
                $this->insertRow('users', $row);
            }
        });

        return [
            'students_migrated' => count($students),
            'users_migrated' => count($users),
        ];
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $sourceDb = $argv[1] ?? null;
    $tenantId = isset($argv[2]) ? (int) $argv[2] : null;

    if (!$sourceDb || !$tenantId) {
        fwrite(STDERR, "Usage: php MergeSchoolData.php <source_database_name> <tenant_id>\n");
        exit(1);
    }

    $source = new PDO("mysql:host=127.0.0.1;dbname={$sourceDb};charset=utf8mb4", 'root', '');
    $source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $target = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
    $target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $merger = new MergeSchoolData($source, $target, $tenantId);
    $result = $merger->run();

    echo "Migrated {$result['students_migrated']} students and {$result['users_migrated']} users for tenant {$tenantId}.\n";
}
