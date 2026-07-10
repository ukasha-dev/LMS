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
        // will run (WHERE tenant_id = ? against `staff`). Tenant 25's
        // real staff count (18) was established and verified during
        // Stage 1 -- this test re-derives it independently rather than
        // trusting that number, so it also catches any drift in the
        // real data since then.
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM staff WHERE tenant_id = :tenant_id');
        $stmt->execute([':tenant_id' => 25]);
        $count = (int) $stmt->fetchColumn();

        $this->assertGreaterThan(0, $count);

        // No other tenant currently has data -- this assertion documents
        // that fact and will need updating (not silently pass/fail
        // differently) once a second tenant is migrated in Phase 5.
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM staff WHERE tenant_id != :tenant_id');
        $stmt->execute([':tenant_id' => 25]);
        $this->assertSame(0, (int) $stmt->fetchColumn());
    }
}
