<?php

final class RealLoginGate
{
    public function __construct(private PDO $pdo)
    {
    }

    public function verify(string $email, string $password, int $tenantId, callable $passwordVerifier, callable $legacyFallback): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT password FROM staff WHERE email = :email AND tenant_id = :tenant_id LIMIT 1'
        );
        $stmt->execute(['email' => $email, 'tenant_id' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row !== false && $passwordVerifier($password, $row['password'])) {
            return ['success' => true, 'source' => 'school_saas'];
        }

        if ($legacyFallback()) {
            return ['success' => true, 'source' => 'legacy'];
        }

        return ['success' => false, 'source' => 'none'];
    }
}
