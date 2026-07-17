<?php

final class RealLoginGate
{
    public function __construct(private PDO $pdo)
    {
    }

    // A school_saas match is only trusted as authoritative for $tenantId
    // if it is UNIQUE -- i.e. no other tenant's staff row with the same
    // email also verifies against the submitted password. school_saas
    // already holds every real tenant's data in one table, so this is a
    // single-table check, not a cross-database lookup, and doesn't
    // compromise tenant isolation: it never reveals or uses another
    // tenant's data beyond "does this password also verify there,"
    // purely to avoid mis-routing a shared credential (e.g. a vendor/
    // support account intentionally provisioned identically across
    // every tenant) into the wrong tenant's session. When ambiguous, the
    // caller's legacy fallback decides instead -- preserving whatever
    // the pre-existing, untouched legacy behavior for that credential
    // already was, rather than this class inventing a new, confident
    // answer for a case it cannot actually disambiguate.
    public function verify(string $email, string $password, int $tenantId, callable $passwordVerifier, callable $legacyFallback): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT password FROM staff WHERE email = :email AND tenant_id = :tenant_id LIMIT 1'
        );
        $stmt->execute(['email' => $email, 'tenant_id' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row !== false
            && $passwordVerifier($password, $row['password'])
            && !$this->matchesUnderAnotherTenant($email, $password, $tenantId, $passwordVerifier)
        ) {
            return ['success' => true, 'source' => 'school_saas'];
        }

        if ($legacyFallback()) {
            return ['success' => true, 'source' => 'legacy'];
        }

        return ['success' => false, 'source' => 'none'];
    }

    private function matchesUnderAnotherTenant(string $email, string $password, int $tenantId, callable $passwordVerifier): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT password FROM staff WHERE email = :email AND tenant_id != :tenant_id'
        );
        $stmt->execute(['email' => $email, 'tenant_id' => $tenantId]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $otherRow) {
            if ($passwordVerifier($password, $otherRow['password'])) {
                return true;
            }
        }

        return false;
    }
}
