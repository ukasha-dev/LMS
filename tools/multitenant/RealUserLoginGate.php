<?php

final class RealUserLoginGate
{
    public function __construct(private PDO $pdo)
    {
    }

    // A school_saas match is only trusted as authoritative for $tenantId if it is
    // UNIQUE -- i.e. no other tenant's users row with the same matched identifying
    // value also verifies against the submitted password. school_saas already
    // holds every real tenant's data in one table, so this is a single-table
    // check, not a cross-database lookup, and doesn't compromise tenant isolation:
    // it never reveals or uses another tenant's data beyond "does this password
    // also verify there," purely to avoid mis-routing a shared/template-cloned
    // credential (see the smart_school student-account collisions found live
    // during this stage's design) into the wrong tenant's session. When
    // ambiguous, the caller's legacy fallback decides instead -- preserving
    // whatever the pre-existing, untouched legacy behavior for that credential
    // already was, rather than this class inventing a new, confident answer for
    // a case it cannot actually disambiguate.
    public function verify(string $identifier, string $password, int $tenantId, callable $passwordVerifier, callable $legacyFallback): array
    {
        $row = $this->findMatch($identifier, $tenantId);

        if ($row !== null
            && $passwordVerifier($password, $row['password'])
            && !$this->matchesUnderAnotherTenant($identifier, $password, $tenantId, $passwordVerifier)
        ) {
            return ['success' => true, 'source' => 'school_saas'];
        }

        if ($legacyFallback()) {
            return ['success' => true, 'source' => 'legacy'];
        }

        return ['success' => false, 'source' => 'none'];
    }

    private function findMatch(string $identifier, int $tenantId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT users.password AS password
             FROM users
             LEFT JOIN students
               ON (students.id = users.user_id OR students.parent_id = users.id)
               AND students.tenant_id = users.tenant_id
             WHERE users.tenant_id = :tenant_id
             AND (
               users.username = :identifier
               OR students.admission_no = :identifier
               OR students.mobileno = :identifier
               OR students.email = :identifier
               OR students.guardian_phone = :identifier
               OR students.guardian_email = :identifier
             )
             LIMIT 1'
        );
        $stmt->execute(['identifier' => $identifier, 'tenant_id' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $row;
    }

    private function matchesUnderAnotherTenant(string $identifier, string $password, int $tenantId, callable $passwordVerifier): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT users.password AS password
             FROM users
             LEFT JOIN students
               ON (students.id = users.user_id OR students.parent_id = users.id)
               AND students.tenant_id = users.tenant_id
             WHERE users.tenant_id != :tenant_id
             AND (
               users.username = :identifier
               OR students.admission_no = :identifier
               OR students.mobileno = :identifier
               OR students.email = :identifier
               OR students.guardian_phone = :identifier
               OR students.guardian_email = :identifier
             )'
        );
        $stmt->execute(['identifier' => $identifier, 'tenant_id' => $tenantId]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $otherRow) {
            if ($passwordVerifier($password, $otherRow['password'])) {
                return true;
            }
        }

        return false;
    }
}
