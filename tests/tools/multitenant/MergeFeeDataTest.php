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
        $studentSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL';
        $classSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, class VARCHAR(60) DEFAULT NULL';
        $sectionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, section VARCHAR(60) DEFAULT NULL';
        $studentSessionSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, class_id INT NOT NULL, section_id INT NOT NULL,'
            . " created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
        $sfmSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, is_system INT NOT NULL DEFAULT 0, student_session_id INT DEFAULT NULL,'
            . ' fee_session_group_id INT DEFAULT NULL, amount FLOAT(10,2) DEFAULT 0.00, pre_discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,'
            . ' is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $sfDiscSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_session_id INT DEFAULT NULL, fees_discount_id INT DEFAULT NULL,'
            . ' status VARCHAR(20) DEFAULT \'assigned\', payment_id VARCHAR(50) DEFAULT NULL, description TEXT DEFAULT NULL,'
            . ' is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $sfDepositeSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_fees_master_id INT DEFAULT NULL, fee_groups_feetype_id INT DEFAULT NULL,'
            . ' student_transport_fee_id INT DEFAULT NULL, amount_detail TEXT DEFAULT NULL, is_active VARCHAR(10) NOT NULL DEFAULT \'no\','
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $sadSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, student_fees_deposite_id INT DEFAULT NULL, student_fees_discount_id INT DEFAULT NULL,'
            . ' date DATE DEFAULT NULL, invoice_id INT DEFAULT NULL, sub_invoice_id INT DEFAULT NULL,'
            . ' created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE sessions ({$sessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE feetype ({$feetypeSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_groups ({$feeGroupsSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fees_discounts ({$feesDiscountsSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_session_groups ({$fsgSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fee_groups_feetype ({$fgfSchema}{$tenantCol})");
            $db->exec("CREATE TABLE fees_reminder ({$reminderSchema}{$tenantCol})");
            $db->exec("CREATE TABLE students ({$studentSchema}{$tenantCol})");
            $db->exec("CREATE TABLE classes ({$classSchema}{$tenantCol})");
            $db->exec("CREATE TABLE sections ({$sectionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_session ({$studentSessionSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_fees_master ({$sfmSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_fees_discounts ({$sfDiscSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_fees_deposite ({$sfDepositeSchema}{$tenantCol})");
            $db->exec("CREATE TABLE student_applied_discounts ({$sadSchema}{$tenantCol})");
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

    public function testResolvesReferencedSessionsEvenWhenAnUnrelatedDuplicateSessionNameExistsElsewhere(): void
    {
        // Mirrors the real al_hafeez_campus collision: TWO unrelated
        // sessions elsewhere in the table share a name ("2025-26"), but
        // nothing being migrated references either of them. Must NOT
        // throw, and the actually-referenced session must still resolve
        // correctly.
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25'), (21, '2025-26'), (26, '2025-26')");
        $this->target->exec("INSERT INTO sessions (id, session, tenant_id) VALUES (2, '2024-25', 25), (10, '2025-26', 25), (15, '2025-26', 25)");

        $this->source->exec("INSERT INTO feetype (id, type, code, nature, session_id) VALUES (5, 'Tuition Fee', 'TUI', 'monthly', 20)");

        $merger = new MergeFeeData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['feetype_migrated']);
        $this->assertSame(0, $result['feetype_skipped']);

        $feetype = $this->target->query('SELECT * FROM feetype')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(2, (int) $feetype['session_id']);
    }

    public function testThrowsWhenAnActuallyReferencedSessionNameIsAmbiguous(): void
    {
        // Unlike the test above, THIS session name collision is directly
        // referenced by the row being migrated -- must still throw,
        // exactly like the pre-fix behavior for a genuine ambiguity.
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->target->exec("INSERT INTO sessions (id, session, tenant_id) VALUES (2, '2024-25', 25), (3, '2024-25', 25)");

        $this->source->exec("INSERT INTO feetype (id, type, code, nature, session_id) VALUES (5, 'Tuition Fee', 'TUI', 'monthly', 20)");

        $merger = new MergeFeeData($this->source, $this->target, 25);

        $this->expectException(RuntimeException::class);
        $merger->run();
    }

    public function testReconnectsStudentFeesMasterAndDiscountsToAlreadyMigratedData(): void
    {
        // Catalog chain (same shape as the Part A test).
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->target->exec("INSERT INTO sessions (id, session, tenant_id) VALUES (2, '2024-25', 25)");
        $this->source->exec("INSERT INTO fee_groups (id, name, nature) VALUES (8, 'General Fee', 'monthly')");
        $this->source->exec("INSERT INTO fee_session_groups (id, fee_groups_id, session_id) VALUES (30, 8, 20)");
        $this->source->exec("INSERT INTO fees_discounts (id, session_id, name, code, type, percentage) VALUES (12, 20, 'Sibling Discount', 'SIB', 'percentage', 10.00)");

        // Student/session chain -- old ids in source (100s/400s), already
        // migrated NEW ids in target (1s/4s), deliberately non-overlapping.
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec("INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES (401, 101, 201, 301, '2025-01-01 00:00:00')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec("INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES (4, 1, 2, 3, 25, '2025-01-01 00:00:00')");

        $this->source->exec(
            "INSERT INTO student_fees_master (id, student_session_id, fee_session_group_id, amount) VALUES (500, 401, 30, 1500.00)"
        );
        $this->source->exec(
            "INSERT INTO student_fees_discounts (id, student_session_id, fees_discount_id, status) VALUES (600, 401, 12, 'assigned')"
        );

        $merger = new MergeFeeData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['student_fees_master_migrated']);
        $this->assertSame(1, $result['student_fees_master_source_total']);
        $this->assertSame(0, $result['student_fees_master_skipped']);
        $this->assertSame(1, $result['student_fees_discounts_migrated']);
        $this->assertSame(1, $result['student_fees_discounts_source_total']);
        $this->assertSame(0, $result['student_fees_discounts_skipped']);

        $sfm = $this->target->query('SELECT * FROM student_fees_master')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(4, (int) $sfm['student_session_id']);
        $this->assertSame(25, (int) $sfm['tenant_id']);

        $sfDisc = $this->target->query('SELECT * FROM student_fees_discounts')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(4, (int) $sfDisc['student_session_id']);
        $this->assertSame(25, (int) $sfDisc['tenant_id']);
    }

    public function testSkipsStudentFeesRowsReferencingAnUnmigratedStudentSession(): void
    {
        $this->source->exec("INSERT INTO fee_groups (id, name, nature) VALUES (8, 'General Fee', 'monthly')");
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->target->exec("INSERT INTO sessions (id, session, tenant_id) VALUES (2, '2024-25', 25)");
        $this->source->exec("INSERT INTO fee_session_groups (id, fee_groups_id, session_id) VALUES (30, 8, 20)");
        // student_session_id 999 has no corresponding row anywhere --
        // simulates a dangling reference that must be skipped, not
        // inserted broken, and must be counted.
        $this->source->exec(
            "INSERT INTO student_fees_master (id, student_session_id, fee_session_group_id, amount) VALUES (500, 999, 30, 1500.00)"
        );

        $merger = new MergeFeeData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(0, $result['student_fees_master_migrated']);
        $this->assertSame(1, $result['student_fees_master_source_total']);
        $this->assertSame(1, $result['student_fees_master_skipped']);
    }

    public function testReconnectsDepositeAndAppliedDiscountsToThisRunsOwnRemappedRows(): void
    {
        // Full chain: catalog -> student -> master -> deposite -> applied discount.
        $this->source->exec("INSERT INTO sessions (id, session) VALUES (20, '2024-25')");
        $this->target->exec("INSERT INTO sessions (id, session, tenant_id) VALUES (2, '2024-25', 25)");
        $this->source->exec("INSERT INTO fee_groups (id, name, nature) VALUES (8, 'General Fee', 'monthly')");
        $this->source->exec("INSERT INTO fee_session_groups (id, fee_groups_id, session_id) VALUES (30, 8, 20)");
        $this->source->exec("INSERT INTO feetype (id, type, code, nature, session_id) VALUES (5, 'Tuition Fee', 'TUI', 'monthly', 20)");
        $this->source->exec(
            "INSERT INTO fee_groups_feetype (id, fee_session_group_id, fee_groups_id, feetype_id, session_id, amount)"
            . " VALUES (100, 30, 8, 5, 20, 1500.00)"
        );
        $this->source->exec("INSERT INTO fees_discounts (id, session_id, name, code, type, percentage) VALUES (12, 20, 'Sibling Discount', 'SIB', 'percentage', 10.00)");

        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->source->exec("INSERT INTO classes (id, class) VALUES (201, 'Class 1')");
        $this->source->exec("INSERT INTO sections (id, section) VALUES (301, 'A')");
        $this->source->exec("INSERT INTO student_session (id, student_id, class_id, section_id, created_at) VALUES (401, 101, 201, 301, '2025-01-01 00:00:00')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (1, 'ADM-001', 25)");
        $this->target->exec("INSERT INTO classes (id, class, tenant_id) VALUES (2, 'Class 1', 25)");
        $this->target->exec("INSERT INTO sections (id, section, tenant_id) VALUES (3, 'A', 25)");
        $this->target->exec("INSERT INTO student_session (id, student_id, class_id, section_id, tenant_id, created_at) VALUES (4, 1, 2, 3, 25, '2025-01-01 00:00:00')");

        $this->source->exec(
            "INSERT INTO student_fees_master (id, student_session_id, fee_session_group_id, amount) VALUES (500, 401, 30, 1500.00)"
        );
        $this->source->exec(
            "INSERT INTO student_fees_discounts (id, student_session_id, fees_discount_id, status) VALUES (600, 401, 12, 'assigned')"
        );
        $this->source->exec(
            "INSERT INTO student_fees_deposite (id, student_fees_master_id, fee_groups_feetype_id, amount_detail) VALUES (700, 500, 100, '{\"paid\":1500}')"
        );
        $this->source->exec(
            "INSERT INTO student_applied_discounts (id, student_fees_deposite_id, student_fees_discount_id, date, invoice_id) VALUES (800, 700, 600, '2026-01-15', 42)"
        );

        $merger = new MergeFeeData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['student_fees_deposite_migrated']);
        $this->assertSame(1, $result['student_fees_deposite_source_total']);
        $this->assertSame(0, $result['student_fees_deposite_skipped']);
        $this->assertSame(1, $result['student_applied_discounts_migrated']);
        $this->assertSame(1, $result['student_applied_discounts_source_total']);
        $this->assertSame(0, $result['student_applied_discounts_skipped']);

        $deposite = $this->target->query('SELECT * FROM student_fees_deposite')->fetch(PDO::FETCH_ASSOC);
        $master = $this->target->query('SELECT * FROM student_fees_master')->fetch(PDO::FETCH_ASSOC);
        $fgf = $this->target->query('SELECT * FROM fee_groups_feetype')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame((int) $master['id'], (int) $deposite['student_fees_master_id']);
        $this->assertSame((int) $fgf['id'], (int) $deposite['fee_groups_feetype_id']);
        $this->assertNull($deposite['student_transport_fee_id']);
        $this->assertSame(25, (int) $deposite['tenant_id']);

        $applied = $this->target->query('SELECT * FROM student_applied_discounts')->fetch(PDO::FETCH_ASSOC);
        $sfDisc = $this->target->query('SELECT * FROM student_fees_discounts')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame((int) $deposite['id'], (int) $applied['student_fees_deposite_id']);
        $this->assertSame((int) $sfDisc['id'], (int) $applied['student_fees_discount_id']);
        $this->assertSame(42, (int) $applied['invoice_id']);
        $this->assertSame(25, (int) $applied['tenant_id']);
    }
}
