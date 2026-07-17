<?php

use PHPUnit\Framework\TestCase;

final class RealUserLoginGateTest extends TestCase
{
    private PDO $pdo;
    private RealUserLoginGate $gate;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS real_user_login_gate_test');
        $admin->exec('CREATE DATABASE real_user_login_gate_test');

        $this->pdo = new PDO('mysql:host=127.0.0.1;dbname=real_user_login_gate_test;charset=utf8mb4', 'root', '');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, tenant_id INT NOT NULL, user_id INT NOT NULL DEFAULT 0, username VARCHAR(50), password VARCHAR(255), role VARCHAR(30))');
        $this->pdo->exec('CREATE TABLE students (id INT AUTO_INCREMENT PRIMARY KEY, tenant_id INT NOT NULL, parent_id INT NOT NULL DEFAULT 0, admission_no VARCHAR(100), mobileno VARCHAR(100), email VARCHAR(100), guardian_phone VARCHAR(100), guardian_email VARCHAR(100))');

        $this->pdo->exec("INSERT INTO students (id, tenant_id, parent_id, admission_no, mobileno, email, guardian_phone, guardian_email) VALUES (1, 25, 0, 'ADM-1', '5550001', 'stu1@example.com', '5559001', 'guardian1@example.com')");
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (1, 25, 1, 'std113', 'real-password', 'student')");

        $this->gate = new RealUserLoginGate($this->pdo);
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS real_user_login_gate_test');
    }

    private function plainVerifier(): callable
    {
        return fn (string $submitted, string $stored): bool => $submitted === $stored;
    }

    public function testSucceedsViaSchoolSaasWhenUsernameAndPasswordMatchAndNeverCallsLegacyFallback(): void
    {
        $legacyFallback = function (): bool {
            throw new \RuntimeException('legacy fallback must not be called when school_saas already matched');
        };

        $result = $this->gate->verify('std113', 'real-password', 25, $this->plainVerifier(), $legacyFallback);

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testFallsBackToLegacyWhenIdentifierDoesNotExistInSchoolSaas(): void
    {
        $result = $this->gate->verify('nobody', 'anything', 25, $this->plainVerifier(), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testFallsBackToLegacyWhenRowExistsButPasswordDoesNotMatch(): void
    {
        $result = $this->gate->verify('std113', 'wrong-password', 25, $this->plainVerifier(), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testFailsWhenNeitherSchoolSaasNorLegacyMatch(): void
    {
        $result = $this->gate->verify('nobody', 'anything', 25, $this->plainVerifier(), fn (): bool => false);

        $this->assertSame(['success' => false, 'source' => 'none'], $result);
    }

    public function testDoesNotMatchAcrossTenants(): void
    {
        // std113/real-password exists under tenant 25 only in this fixture. Asking
        // under tenant 99 must not match school_saas -- falls through to whatever
        // the legacy fallback says.
        $result = $this->gate->verify('std113', 'real-password', 99, $this->plainVerifier(), fn (): bool => false);

        $this->assertSame(['success' => false, 'source' => 'none'], $result);
    }

    public function testPasswordVerifierReceivesSubmittedAndStoredPasswordInThatOrder(): void
    {
        $seenArgs = null;
        $spy = function (string $submitted, string $stored) use (&$seenArgs): bool {
            $seenArgs = [$submitted, $stored];

            return true;
        };

        $this->gate->verify('std113', 'my-password', 25, $spy, fn (): bool => false);

        $this->assertSame(['my-password', 'real-password'], $seenArgs);
    }

    public function testMatchesViaAdmissionNo(): void
    {
        $result = $this->gate->verify('ADM-1', 'real-password', 25, $this->plainVerifier(), function (): bool {
            throw new \RuntimeException('legacy fallback must not be called on an unambiguous match');
        });

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testMatchesViaMobileno(): void
    {
        $result = $this->gate->verify('5550001', 'real-password', 25, $this->plainVerifier(), function (): bool {
            throw new \RuntimeException('legacy fallback must not be called on an unambiguous match');
        });

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testMatchesViaStudentEmail(): void
    {
        $result = $this->gate->verify('stu1@example.com', 'real-password', 25, $this->plainVerifier(), function (): bool {
            throw new \RuntimeException('legacy fallback must not be called on an unambiguous match');
        });

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testMatchesViaGuardianPhone(): void
    {
        $result = $this->gate->verify('5559001', 'real-password', 25, $this->plainVerifier(), function (): bool {
            throw new \RuntimeException('legacy fallback must not be called on an unambiguous match');
        });

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testMatchesViaGuardianEmail(): void
    {
        $result = $this->gate->verify('guardian1@example.com', 'real-password', 25, $this->plainVerifier(), function (): bool {
            throw new \RuntimeException('legacy fallback must not be called on an unambiguous match');
        });

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testMatchesViaParentLinkedThroughParentId(): void
    {
        // A parent user (users.user_id = 0, not linked via students.id) is joined
        // via students.parent_id = users.id instead -- students.id (2) must equal
        // the parent user's own id.
        $this->pdo->exec("INSERT INTO students (id, tenant_id, parent_id, guardian_email) VALUES (2, 25, 7, 'parent-only@example.com')");
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (7, 25, 0, 'parent113', 'parent-password', 'parent')");

        $result = $this->gate->verify('parent-only@example.com', 'parent-password', 25, $this->plainVerifier(), function (): bool {
            throw new \RuntimeException('legacy fallback must not be called on an unambiguous match');
        });

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testFallsBackToLegacyWhenTheSameCredentialAlsoVerifiesUnderAnotherTenant(): void
    {
        // A shared/template-contaminated account: same username+password exists
        // under both tenant 25 and tenant 30, mirroring the real smart_school
        // collision pattern found live (e.g. std1233/sfxcqb under tenants 26 and
        // 30). Must NOT be trusted as an authoritative tenant-25 match -- falls
        // through to whatever the legacy fallback decides instead.
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (2, 30, 99, 'shared-student', 'shared-password', 'student')");
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (3, 25, 99, 'shared-student', 'shared-password', 'student')");

        $result = $this->gate->verify('shared-student', 'shared-password', 25, $this->plainVerifier(), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testFallsBackToLegacyWhenTheSameCredentialVerifiesUnderMultipleOtherTenants(): void
    {
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (10, 25, 50, 'vendor-student', 'vendor-password', 'student')");
        foreach ([26, 27, 28, 29, 30] as $otherTenant) {
            $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES ({$otherTenant}0, {$otherTenant}, 50, 'vendor-student', 'vendor-password', 'student')");
        }

        $result = $this->gate->verify('vendor-student', 'vendor-password', 25, $this->plainVerifier(), fn (): bool => true);

        $this->assertSame(['success' => true, 'source' => 'legacy'], $result);
    }

    public function testStillSucceedsViaSchoolSaasWhenAnotherTenantHasTheSameUsernameButADifferentPassword(): void
    {
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (11, 30, 60, 'coincidental', 'tenant30-different-password', 'student')");
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (12, 25, 60, 'coincidental', 'tenant25-password', 'student')");

        $legacyFallback = function (): bool {
            throw new \RuntimeException('legacy fallback must not be called for an unambiguous school_saas match');
        };

        $result = $this->gate->verify('coincidental', 'tenant25-password', 25, $this->plainVerifier(), $legacyFallback);

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }

    public function testAmbiguityCheckOnlyConsidersTheRequestedIdentifier(): void
    {
        $this->pdo->exec("INSERT INTO users (id, tenant_id, user_id, username, password, role) VALUES (13, 30, 70, 'unrelated-student', 'real-password', 'student')");

        $legacyFallback = function (): bool {
            throw new \RuntimeException('legacy fallback must not be called when the requested-tenant match is unambiguous');
        };

        $result = $this->gate->verify('std113', 'real-password', 25, $this->plainVerifier(), $legacyFallback);

        $this->assertSame(['success' => true, 'source' => 'school_saas'], $result);
    }
}
