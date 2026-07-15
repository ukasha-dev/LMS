<?php

use PHPUnit\Framework\TestCase;

final class MergeStandaloneTenantDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS standalone_test_source');
        $admin->exec('CREATE DATABASE standalone_test_source');
        $admin->exec('DROP DATABASE IF EXISTS standalone_test_target');
        $admin->exec('CREATE DATABASE standalone_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=standalone_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=standalone_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $schema = 'id INT AUTO_INCREMENT PRIMARY KEY, type VARCHAR(100) DEFAULT NULL, is_default INT DEFAULT 0';
        $this->source->exec("CREATE TABLE holiday_type ({$schema})");
        $this->target->exec("CREATE TABLE holiday_type ({$schema}, tenant_id INT NOT NULL)");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS standalone_test_source');
        $admin->exec('DROP DATABASE IF EXISTS standalone_test_target');
    }

    public function testMergesRowsForTenantWithFreshIds(): void
    {
        $this->source->exec("INSERT INTO holiday_type (id, type, is_default) VALUES (1, 'National', 1), (2, 'Religious', 0)");

        $merger = new MergeStandaloneTenantData($this->source, $this->target, 25, 'holiday_type');
        $result = $merger->run();

        $this->assertSame(2, $result['migrated']);

        $rows = $this->target->query('SELECT * FROM holiday_type ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $rows);
        $this->assertSame('National', $rows[0]['type']);
        $this->assertSame('Religious', $rows[1]['type']);
        $this->assertSame(25, (int) $rows[0]['tenant_id']);
        $this->assertSame(25, (int) $rows[1]['tenant_id']);
    }

    public function testAssignsFreshTargetIdsInsteadOfReusingSourceIds(): void
    {
        $this->target->exec("INSERT INTO holiday_type (id, type, tenant_id) VALUES (1, 'Existing for another tenant', 99)");
        $this->source->exec("INSERT INTO holiday_type (id, type) VALUES (1, 'National')");

        $merger = new MergeStandaloneTenantData($this->source, $this->target, 25, 'holiday_type');
        $merger->run();

        $row = $this->target->query("SELECT * FROM holiday_type WHERE tenant_id = 25")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(2, (int) $row['id']);
        $this->assertSame('National', $row['type']);
    }

    public function testRefusesToRunAgainIfTenantAlreadyHasRows(): void
    {
        $this->target->exec("INSERT INTO holiday_type (id, type, tenant_id) VALUES (1, 'Existing', 25)");
        $this->source->exec("INSERT INTO holiday_type (id, type) VALUES (1, 'National')");

        $merger = new MergeStandaloneTenantData($this->source, $this->target, 25, 'holiday_type');

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected run() to refuse when the tenant already has rows');

        $count = (int) $this->target->query('SELECT COUNT(*) AS c FROM holiday_type')->fetch(PDO::FETCH_ASSOC)['c'];
        $this->assertSame(1, $count, 'Refusing to run must not insert any new rows');
    }
}
