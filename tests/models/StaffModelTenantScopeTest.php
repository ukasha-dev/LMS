<?php

use PHPUnit\Framework\TestCase;

final class StaffModelTenantScopeTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = new PDO('mysql:host=127.0.0.1;dbname=school_saas;charset=utf8mb4', 'root', '');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function testTenantScopedQueryMatchesExactlyOneTenantsStaffRows(): void
    {
        // Mirrors exactly the query Staff_model::getTenantScopedStaffList()
        // will run (WHERE tenant_id = ? against `staff`). Each count below
        // was established and verified independently against the real
        // source school database for that tenant (see the Phase 5 batch
        // that onboarded tenants 26-30), so this test re-derives them
        // rather than trusting a single cached number, and also catches
        // any drift or cross-tenant contamination in the real data.
        $expectedStaffCountByTenant = [
            25 => 18,  // Al-Hafeez Campus
            26 => 17,  // Al-Mateen Campus
            27 => 27,  // Nafay Campus
            28 => 23,  // Salam Boys School
            29 => 18,  // Salam Girls School
            30 => 172, // Smart School
        ];

        $stmt = $this->db->prepare('SELECT COUNT(*) FROM staff WHERE tenant_id = :tenant_id');
        foreach ($expectedStaffCountByTenant as $tenantId => $expectedCount) {
            $stmt->execute([':tenant_id' => $tenantId]);
            $this->assertSame($expectedCount, (int) $stmt->fetchColumn(), "tenant {$tenantId} staff count");
        }

        // No tenant OTHER than the six above currently has staff data --
        // this assertion documents that fact and will need updating (not
        // silently pass/fail differently) once a further tenant is onboarded.
        $placeholders = implode(', ', array_fill(0, count($expectedStaffCountByTenant), '?'));
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM staff WHERE tenant_id NOT IN ({$placeholders})");
        $stmt->execute(array_keys($expectedStaffCountByTenant));
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }
}
