<?php

use PHPUnit\Framework\TestCase;

final class SmartSchoolStaffNeutralizerTest extends TestCase
{
    private PDO $smartSchoolPdo;
    private PDO $schoolAPdo;
    private PDO $schoolBPdo;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        foreach (['neutralizer_test_smart', 'neutralizer_test_a', 'neutralizer_test_b'] as $db) {
            $admin->exec("DROP DATABASE IF EXISTS {$db}");
            $admin->exec("CREATE DATABASE {$db}");
        }

        $this->smartSchoolPdo = new PDO('mysql:host=127.0.0.1;dbname=neutralizer_test_smart;charset=utf8mb4', 'root', '');
        $this->smartSchoolPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->schoolAPdo = new PDO('mysql:host=127.0.0.1;dbname=neutralizer_test_a;charset=utf8mb4', 'root', '');
        $this->schoolAPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->schoolBPdo = new PDO('mysql:host=127.0.0.1;dbname=neutralizer_test_b;charset=utf8mb4', 'root', '');
        $this->schoolBPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $createStaff = 'CREATE TABLE staff (id INT AUTO_INCREMENT PRIMARY KEY, employee_id VARCHAR(50), password VARCHAR(250) NOT NULL, is_active TINYINT(1) NOT NULL)';
        $this->smartSchoolPdo->exec($createStaff);
        $this->schoolAPdo->exec($createStaff);
        $this->schoolBPdo->exec($createStaff);
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        foreach (['neutralizer_test_smart', 'neutralizer_test_a', 'neutralizer_test_b'] as $db) {
            $admin->exec("DROP DATABASE IF EXISTS {$db}");
        }
    }

    private function neutralizer(): SmartSchoolStaffNeutralizer
    {
        return new SmartSchoolStaffNeutralizer($this->smartSchoolPdo, [
            'school_a' => $this->schoolAPdo,
            'school_b' => $this->schoolBPdo,
        ]);
    }

    public function testInactiveRowWithRealCollisionIsSelected(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E1', 'shared-hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E1', 'shared-hash', 1)");

        $result = $this->neutralizer()->dryRun();

        $this->assertSame(1, $result['count']);
        $this->assertSame('E1', $result['candidates'][0]['employee_id']);
        $this->assertSame(['school_a'], $result['candidates'][0]['colliding_with']);
    }

    public function testInactiveRowWithNoCollisionIsExcluded(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E2', 'unique-hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E3', 'different-hash', 1)");

        $result = $this->neutralizer()->dryRun();

        $this->assertSame(0, $result['count']);
    }

    public function testActiveRowWithCollisionIsExcluded(): void
    {
        // Mirrors the real hamza.ali@kics.edu.pk shape: is_active = 1 must
        // exclude the row from selection regardless of collision.
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E4', 'vendor-hash', 1)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E4', 'vendor-hash', 1)");
        $this->schoolBPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E4', 'vendor-hash', 1)");

        $result = $this->neutralizer()->dryRun();

        $this->assertSame(0, $result['count']);
    }

    public function testActiveRowWithNoCollisionIsExcluded(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E5', 'own-hash', 1)");

        $result = $this->neutralizer()->dryRun();

        $this->assertSame(0, $result['count']);
    }

    public function testCollisionAcrossMultipleSchoolsListsAll(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E6', 'multi-hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E6', 'multi-hash', 1)");
        $this->schoolBPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E6', 'multi-hash', 1)");

        $result = $this->neutralizer()->dryRun();

        $this->assertSame(1, $result['count']);
        sort($result['candidates'][0]['colliding_with']);
        $this->assertSame(['school_a', 'school_b'], $result['candidates'][0]['colliding_with']);
    }

    public function testDryRunPerformsZeroWrites(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E7', 'shared-hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E7', 'shared-hash', 1)");

        $this->neutralizer()->dryRun();

        $row = $this->smartSchoolPdo->query("SELECT password FROM staff WHERE employee_id = 'E7'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('shared-hash', $row['password']);
    }

    public function testSentinelValueFitsWithinRealColumnWidth(): void
    {
        // Confirmed live this session: smart_school.staff.password is
        // varchar(250) NOT NULL -- the sentinel must fit comfortably.
        $sentinel = 'NEUTRALIZED-BY-SMART-SCHOOL-CLEANUP-DO-NOT-USE';
        $this->assertLessThanOrEqual(250, strlen($sentinel));
    }

    public function testCaseDifferingPasswordIsNotTreatedAsCollision(): void
    {
        // Task 1 review finding: staff.password across all 6 live school
        // databases uses collation utf8_general_ci (case-insensitive),
        // confirmed via a live information_schema.COLUMNS query. Without an
        // explicit binary comparison, findCollisions() would treat two
        // hashes differing only in letter case as colliding -- not the
        // byte-identical matching the plan's Global Constraints require.
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E12', 'ABC123hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E12', 'abc123hash', 1)");

        $result = $this->neutralizer()->dryRun();

        $this->assertSame(0, $result['count']);
    }

    public function testExecuteLiveMutatesOnlyQualifyingRows(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E8', 'shared-hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E8', 'shared-hash', 1)");
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E9', 'own-hash', 0)");

        $result = $this->neutralizer()->executeLive();

        $this->assertSame(1, $result['updated']);

        $mutated = $this->smartSchoolPdo->query("SELECT password FROM staff WHERE employee_id = 'E8'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('NEUTRALIZED-BY-SMART-SCHOOL-CLEANUP-DO-NOT-USE', $mutated['password']);

        $untouched = $this->smartSchoolPdo->query("SELECT password FROM staff WHERE employee_id = 'E9'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('own-hash', $untouched['password']);
    }

    public function testExecuteLiveNeverTouchesOtherSchoolTables(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E10', 'shared-hash', 0)");
        $this->schoolAPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E10', 'shared-hash', 1)");

        $this->neutralizer()->executeLive();

        $schoolARow = $this->schoolAPdo->query("SELECT password FROM staff WHERE employee_id = 'E10'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('shared-hash', $schoolARow['password']);
    }

    public function testExecuteLiveOnZeroCandidatesUpdatesNothing(): void
    {
        $this->smartSchoolPdo->exec("INSERT INTO staff (employee_id, password, is_active) VALUES ('E11', 'own-hash', 0)");

        $result = $this->neutralizer()->executeLive();

        $this->assertSame(0, $result['updated']);
    }
}
