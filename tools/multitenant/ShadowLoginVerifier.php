<?php

final class ShadowLoginVerifier
{
    public function __construct(private PDO $pdo)
    {
    }

    public function verify(string $email, string $password, int $tenantId, callable $passwordVerifier): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT password FROM staff WHERE email = :email AND tenant_id = :tenant_id LIMIT 1'
        );
        $stmt->execute(['email' => $email, 'tenant_id' => $tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return ['matched' => false, 'reason' => 'no_matching_row'];
        }

        if (!$passwordVerifier($password, $row['password'])) {
            return ['matched' => false, 'reason' => 'password_mismatch'];
        }

        return ['matched' => true, 'reason' => 'ok'];
    }
}
