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

    public function testFallsBackToLegacyWhenTheSameCredentialAlsoVerifiesUnderAnotherTenant(): void
    {
        // A shared/vendor account: same email+password-verifying-hash exists
        // under both tenant 25 and tenant 30 (mirrors the real
        // hamza.ali@kics.edu.pk case found live -- one hash shared across
        // every real tenant). Must NOT be trusted as an authoritative
        // tenant-25 match, since it can't actually be disambiguated --
        // falls through to whatever the legacy fallback decides instead.
        $this->pdo->exec("INSERT INTO staff (email, password, tenant_id) VALUES ('shared@example.com', 'shared-hash', 25)");
        $this->pdo->exec("INSERT INTO staff (email, password, tenant_id) VALUES ('shared@example.com', 'shared-hash', 30)");

        $result = $this->gate->verify('shared@example.com', 'anything', 25, $this->verifierMatching('shared-hash'), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testFallsBackToLegacyWhenTheSameCredentialVerifiesUnderMultipleOtherTenants(): void
    {
        // Same shape as the real hamza.ali@kics.edu.pk case: identical hash
        // present under many tenants, not just one other.
        $this->pdo->exec("INSERT INTO staff (email, password, tenant_id) VALUES ('vendor@example.com', 'vendor-hash', 25)");
        foreach ([26, 27, 28, 29, 30] as $otherTenant) {
            $this->pdo->exec("INSERT INTO staff (email, password, tenant_id) VALUES ('vendor@example.com', 'vendor-hash', $otherTenant)");
        }

        $result = $this->gate->verify('vendor@example.com', 'anything', 25, $this->verifierMatching('vendor-hash'), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testStillSucceedsViaSchoolSaasWhenAnotherTenantHasTheSameEmailButADifferentPassword(): void
    {
        // Same email exists under another tenant too, but with a DIFFERENT
        // stored hash (a coincidental email reuse, not a shared credential)
        // -- the requested tenant's own match is still unique with respect
        // to the submitted password and must be trusted normally.
        $this->pdo->exec("INSERT INTO staff (email, password, tenant_id) VALUES ('coincidental@example.com', 'tenant25-hash', 25)");
        $this->pdo->exec("INSERT INTO staff (email, password, tenant_id) VALUES ('coincidental@example.com', 'tenant30-different-hash', 30)");

        $verifier = function (string $password, string $storedHash): bool {
            return $storedHash === 'tenant25-hash';
        };
        $legacyFallback = function (): bool {
            throw new \RuntimeException('legacy fallback must not be called for an unambiguous school_saas match');
        };

        $result = $this->gate->verify('coincidental@example.com', 'anything', 25, $verifier, $legacyFallback);

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testAmbiguityCheckOnlyConsidersTheRequestedEmail(): void
    {
        // A different email under another tenant, even with the same
        // stored hash, must not cause a false ambiguity match -- the
        // ambiguity check is scoped by email, matching the primary lookup.
        $this->pdo->exec("INSERT INTO staff (email, password, tenant_id) VALUES ('unrelated@example.com', 'school-saas-hash', 30)");

        $legacyFallback = function (): bool {
            throw new \RuntimeException('legacy fallback must not be called when the requested-tenant match is unambiguous');
        };

        $result = $this->gate->verify('real@example.com', 'anything', 25, $this->verifierMatching('school-saas-hash'), $legacyFallback);

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }
}
