<?php

use PHPUnit\Framework\TestCase;

final class RealLoginGateTest extends TestCase
{
    private PDO $pdo;
    private RealLoginGate $gate;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS real_login_gate_test');
        $admin->exec('CREATE DATABASE real_login_gate_test');

        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=real_login_gate_test;charset=utf8mb4', 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE staff (id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(200), password VARCHAR(255), tenant_id INT NOT NULL)');
        $this->pdo->exec("INSERT INTO staff (email, password, tenant_id) VALUES ('real@example.com', 'school-saas-hash', 25)");

        $this->gate = new RealLoginGate($this->pdo);
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS real_login_gate_test');
    }

    private function verifierMatching(string $expectedHash): callable
    {
        return function (string $password, string $storedHash) use ($expectedHash): bool {
            return $storedHash === $expectedHash;
        };
    }

    public function testSucceedsViaSchoolSaasWhenPasswordMatchesThereAndNeverCallsLegacyFallback(): void
    {
        $legacyFallback = function (): bool {
            throw new \RuntimeException('legacy fallback must not be called when school_saas already matched');
        };

        $result = $this->gate->verify('real@example.com', 'anything', 25, $this->verifierMatching('school-saas-hash'), $legacyFallback);

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testFallsBackToLegacyWhenEmailDoesNotExistInSchoolSaas(): void
    {
        $result = $this->gate->verify('nobody@example.com', 'anything', 25, $this->verifierMatching('school-saas-hash'), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testFallsBackToLegacyWhenSchoolSaasRowExistsButPasswordDoesNotMatch(): void
    {
        $result = $this->gate->verify('real@example.com', 'wrong-password', 25, $this->verifierMatching('a-different-hash'), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testFailsWhenNeitherSchoolSaasNorLegacyMatch(): void
    {
        $result = $this->gate->verify('nobody@example.com', 'anything', 25, $this->verifierMatching('school-saas-hash'), fn (): bool => false);

        $this->assertSame(['success' => false, 'source' => 'none'], $result);
    }

    public function testDoesNotMatchAcrossTenants(): void
    {
        // real@example.com exists under tenant 25 only in this fixture. Asking under
        // tenant 99 must not match school_saas, proving the WHERE clause is
        // tenant-scoped -- falls through to whatever the legacy fallback says.
        $result = $this->gate->verify('real@example.com', 'anything', 99, $this->verifierMatching('school-saas-hash'), fn (): bool => false);

        $this->assertSame(['success' => false, 'source' => 'none'], $result);
    }

    public function testPasswordVerifierReceivesThePlaintextPasswordAndStoredHashInThatOrder(): void
    {
        $seenArgs = null;
        $spy = function (string $password, string $storedHash) use (&$seenArgs): bool {
            $seenArgs = [$password, $storedHash];

            return true;
        };

        $this->gate->verify('real@example.com', 'my-password', 25, $spy, fn (): bool => false);

        $this->assertSame(['my-password', 'school-saas-hash'], $seenArgs);
    }
}
