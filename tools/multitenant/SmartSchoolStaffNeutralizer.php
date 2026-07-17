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

    private function findCollisions(string $password): array
    {
        $collidingWith = [];
        foreach ($this->otherSchoolPdos as $schoolName => $pdo) {
            $stmt = $pdo->prepare('SELECT 1 FROM staff WHERE password = :password LIMIT 1');
            $stmt->execute(['password' => $password]);
            if ($stmt->fetch(PDO::FETCH_ASSOC) !== false) {
                $collidingWith[] = $schoolName;
            }
        }

        return $collidingWith;
    }
}
