<?php

use PHPUnit\Framework\TestCase;

final class MergeFeeDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_fee_test_source');
        $admin->exec('CREATE DATABASE merge_fee_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_fee_test_target');
        $admin->exec('CREATE DATABASE merge_fee_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_fee_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_fee_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, session VARCHAR(60) DEFAULT NULL';
        $feetypeSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, is_system INT NOT NULL DEFAULT 0, type VARCHAR(50) DEFAULT NULL,'
            . ' code VARCHAR(100) NOT NULL, is_active VARCHAR(255) DEFAULT NULL, description TEXT DEFAULT NULL,'
            . ' session_id INT DEFAULT NULL, nature VARCHAR(255) NOT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $feeGroupsSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(200) DEFAULT NULL, is_system INT NOT NULL DEFAULT 0,'
            . ' description TEXT DEFAULT NULL, nature VARCHAR(255) NOT NULL, is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $feesDiscountsSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, session_id INT DEFAULT NULL, name VARCHAR(100) DEFAULT NULL,'
            . ' code VARCHAR(100) DEFAULT NULL, type VARCHAR(20) DEFAULT NULL, percentage FLOAT(10,2) DEFAULT NULL,'
            . ' amount FLOAT(10,2) DEFAULT NULL, discount_limit INT DEFAULT NULL, expire_date DATE DEFAULT NULL,'
            . ' description TEXT DEFAULT NULL, is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $fsgSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, fee_groups_id INT DEFAULT NULL, session_id INT DEFAULT NULL,'
            . ' is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $fgfSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, fee_session_group_id INT DEFAULT NULL, fee_groups_id INT DEFAULT NULL,'
            . ' feetype_id INT DEFAULT NULL, session_id INT DEFAULT NULL, amount DECIMAL(10,2) DEFAULT NULL,'
            . ' fine_type VARCHAR(50) NOT NULL DEFAULT \'none\', due_date DATE DEFAULT NULL,'
            . ' fine_percentage FLOAT(10,2) NOT NULL DEFAULT 0.00, fine_amount FLOAT(10,2) NOT NULL DEFAULT 0.00,'
            . ' fine_per_day INT NOT NULL DEFAULT 0, is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $reminderSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, reminder_type VARCHAR(10) DEFAULT NULL, day INT DEFAULT NULL,'
            . ' is_active INT DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE sessions ({$sessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE feetype ({$feetypeSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_groups ({$feeGroupsSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fees_discounts ({$feesDiscountsSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_session_groups ({$fsgSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_groups_feetype ({$fgfSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fees_reminder ({$reminderSchema}{$tenantCol})");
        }
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_fee_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_fee_test_target');
    }

    public function testMergesCatalogTablesWithRemappedIdsAndReconnectedSessions(): void
    {
        // Source session id (20) deliberately differs from the target's
        // already-migrated id for the SAME session name (2), to prove
        // the merge resolves by name, not by copying the source id.
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->target->exec("INSERT INTO sessions (id, session, tenant_id) VALUES (2, '2024-25', 25)");

        $this->source->exec("INSERT INTO feetype (id, type, code, nature, session_id) VALUES (5, 'Tuition Fee', 'TUI', 'monthly', 20)");
        $this->source->exec("INSERT INTO fee_groups (id, name, nature) VALUES (8, 'General Fee', 'monthly')");
        $this->source->exec("INSERT INTO fees_discounts (id, session_id, name, code, type, percentage) VALUES (12, 20, 'Sibling Discount', 'SIB', 'percentage', 10.00)");
        $this->source->exec("INSERT INTO fee_session_groups (id, fee_groups_id, session_id) VALUES (30, 8, 20)");
        $this->source->exec(
            "INSERT INTO fee_groups_feetype (id, fee_session_group_id, fee_groups_id, feetype_id, session_id, amount)"
            . " VALUES (100, 30, 8, 5, 20, 1500.00)"
        );
        $this->source->exec("INSERT INTO fees_reminder (id, reminder_type, day) VALUES (1, 'due', 3)");

        $merger = new MergeFeeData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['feetype_migrated']);
        $this->assertSame(0, $result['feetype_skipped']);
        $this->assertSame(1, $result['fee_groups_migrated']);
        $this->assertSame(1, $result['fees_discounts_migrated']);
        $this->assertSame(0, $result['fees_discounts_skipped']);
        $this->assertSame(1, $result['fee_session_groups_migrated']);
        $this->assertSame(0, $result['fee_session_groups_skipped']);
        $this->assertSame(1, $result['fee_groups_feetype_migrated']);
        $this->assertSame(0, $result['fee_groups_feetype_skipped']);
        $this->assertSame(1, $result['fees_reminder_migrated']);

        $session = $this->target->query("SELECT * FROM sessions WHERE session='2024-25'")->fetch(PDO::FETCH_ASSOC);
        $feetype = $this->target->query('SELECT * FROM feetype')->fetch(PDO::FETCH_ASSOC);
        $feeGroup = $this->target->query('SELECT * FROM fee_groups')->fetch(PDO::FETCH_ASSOC);
        $discount = $this->target->query('SELECT * FROM fees_discounts')->fetch(PDO::FETCH_ASSOC);
        $fsg = $this->target->query('SELECT * FROM fee_session_groups')->fetch(PDO::FETCH_ASSOC);
        $fgf = $this->target->query('SELECT * FROM fee_groups_feetype')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(2, (int) $session['id']);
        $this->assertSame((int) $session['id'], (int) $feetype['session_id']);
        $this->assertSame(25, (int) $feetype['tenant_id']);
        $this->assertSame((int) $session['id'], (int) $discount['session_id']);
        $this->assertSame((int) $feeGroup['id'], (int) $fsg['fee_groups_id']);
        $this->assertSame((int) $session['id'], (int) $fsg['session_id']);
        $this->assertSame((int) $fsg['id'], (int) $fgf['fee_session_group_id']);
        $this->assertSame((int) $feeGroup['id'], (int) $fgf['fee_groups_id']);
        $this->assertSame((int) $feetype['id'], (int) $fgf['feetype_id']);
        $this->assertSame((int) $session['id'], (int) $fgf['session_id']);
        $this->assertSame('1500.00', $fgf['amount']);
        $this->assertSame(25, (int) $fgf['tenant_id']);
    }
}
