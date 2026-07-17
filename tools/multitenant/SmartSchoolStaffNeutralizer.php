<?php

final class SmartSchoolStaffNeutralizer
{
    private const SENTINEL_PASSWORD = 'NEUTRALIZED-BY-SMART-SCHOOL-CLEANUP-DO-NOT-USE';

    /** @var array<string, PDO> */
    private array $otherSchoolPdos;

    public function __construct(private PDO $smartSchoolPdo, array $otherSchoolPdos)
    {
        $this->otherSchoolPdos = $otherSchoolPdos;
    }

    public function dryRun(): array
    {
        $candidates = [];

        $stmt = $this->smartSchoolPdo->query('SELECT id, employee_id, password FROM staff WHERE is_active = 0');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $collidingWith = $this->findCollisions($row['password']);
            if (!empty($collidingWith)) {
                $candidates[] = [
                    'id' => (int) $row['id'],
                    'employee_id' => $row['employee_id'],
                    'colliding_with' => $collidingWith,
                ];
            }
        }

        return ['candidates' => $candidates, 'count' => count($candidates)];
    }

    public function executeLive(): array
    {
        $preCheck = $this->dryRun();

        $updated = 0;
        $this->smartSchoolPdo->beginTransaction();
        try {
            $updateStmt = $this->smartSchoolPdo->prepare('UPDATE staff SET password = :sentinel WHERE id = :id');
            foreach ($preCheck['candidates'] as $candidate) {
                $updateStmt->execute(['sentinel' => self::SENTINEL_PASSWORD, 'id' => $candidate['id']]);
                $updated++;
            }
            $this->smartSchoolPdo->commit();
        } catch (\Throwable $e) {
            $this->smartSchoolPdo->rollBack();
            throw $e;
        }

        return ['updated' => $updated, 'candidates' => $preCheck['candidates']];
    }

    private function findCollisions(string $password): array
    {
        $collidingWith = [];
        foreach ($this->otherSchoolPdos as $schoolName => $pdo) {
            $stmt = $pdo->prepare('SELECT 1 FROM staff WHERE BINARY password = :password LIMIT 1');
            $stmt->execute(['password' => $password]);
            if ($stmt->fetch(PDO::FETCH_ASSOC) !== false) {
                $collidingWith[] = $schoolName;
            }
        }

        return $collidingWith;
    }
}

if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $live = in_array('--live', $argv, true);

    $smartSchoolPdo = new PDO('mysql:host=127.0.0.1;dbname=smart_school;charset=utf8mb4', 'root', '');
    $smartSchoolPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $otherSchoolNames = ['al_hafeez_campus', 'al_mateen_campus', 'nafay_campus', 'salam_boys_school', 'salam_girls_school'];
    $otherSchoolPdos = [];
    foreach ($otherSchoolNames as $name) {
        $pdo = new PDO("mysql:host=127.0.0.1;dbname={$name};charset=utf8mb4", 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $otherSchoolPdos[$name] = $pdo;
    }

    $neutralizer = new SmartSchoolStaffNeutralizer($smartSchoolPdo, $otherSchoolPdos);

    if ($live) {
        $result = $neutralizer->executeLive();
        echo "LIVE RUN: neutralized {$result['updated']} row(s).\n";
        foreach ($result['candidates'] as $c) {
            echo "  id={$c['id']} employee_id={$c['employee_id']} colliding_with=" . implode(',', $c['colliding_with']) . "\n";
        }
    } else {
        $result = $neutralizer->dryRun();
        echo "DRY RUN (no changes made): {$result['count']} row(s) would be neutralized.\n";
        foreach ($result['candidates'] as $c) {
            echo "  id={$c['id']} employee_id={$c['employee_id']} colliding_with=" . implode(',', $c['colliding_with']) . "\n";
        }
        echo "\nRe-run with --live to actually apply this change.\n";
    }
}
