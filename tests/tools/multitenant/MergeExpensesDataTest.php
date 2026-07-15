<?php

use PHPUnit\Framework\TestCase;

final class MergeExpensesDataTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS merge_expenses_test_source');
        $admin->exec('CREATE DATABASE merge_expenses_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_expenses_test_target');
        $admin->exec('CREATE DATABASE merge_expenses_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=merge_expenses_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=merge_expenses_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $headSchema = "id INT AUTO_INCREMENT PRIMARY KEY, exp_category VARCHAR(50) DEFAULT NULL, is_active VARCHAR(10) NOT NULL DEFAULT 'yes'";
        $expenseSchema = 'id INT AUTO_INCREMENT PRIMARY KEY, exp_head_id INT DEFAULT NULL, name VARCHAR(50) DEFAULT NULL,'
            . ' invoice_no VARCHAR(200) DEFAULT NULL, date DATE DEFAULT NULL, amount FLOAT(10,2) DEFAULT NULL,'
            . " documents VARCHAR(255) DEFAULT NULL, note TEXT DEFAULT NULL, is_active VARCHAR(255) DEFAULT 'yes',"
            . " is_deleted VARCHAR(255) DEFAULT 'no', created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,"
            . ' updated_at DATETIME DEFAULT CURRENT_TIMESTAMP';

        foreach ([$this->source, $this->target] as $db) {
            $tenantCol = $db === $this->target ? ', tenant_id INT NOT NULL' : '';
            $db->exec("CREATE TABLE expense_head ({$headSchema}{$tenantCol})");
            $db->exec("CREATE TABLE expenses ({$expenseSchema}{$tenantCol})");
        }
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS merge_expenses_test_source');
        $admin->exec('DROP DATABASE IF EXISTS merge_expenses_test_target');
    }

    public function testMergesExpensesWithResolvedExpenseHeadId(): void
    {
        // expense_head is migrated separately (MergeStandaloneTenantData)
        // with a fresh id -- source id 501 does not match target id 1.
        $this->source->exec("INSERT INTO expense_head (id, exp_category) VALUES (501, 'Utilities')");
        $this->target->exec("INSERT INTO expense_head (id, exp_category, tenant_id) VALUES (1, 'Utilities', 25)");
        $this->source->exec("INSERT INTO expenses (id, exp_head_id, name, amount) VALUES (901, 501, 'Electricity Bill', 150.00)");

        $merger = new MergeExpensesData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['expenses_migrated']);
        $this->assertSame(0, $result['expenses_skipped']);

        $row = $this->target->query('SELECT * FROM expenses')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int) $row['exp_head_id']);
        $this->assertSame('Electricity Bill', $row['name']);
        $this->assertSame(25, (int) $row['tenant_id']);
    }

    public function testMergesExpenseWithNullExpenseHeadId(): void
    {
        $this->source->exec("INSERT INTO expenses (id, exp_head_id, name, amount) VALUES (901, NULL, 'Misc', 20.00)");

        $merger = new MergeExpensesData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['expenses_migrated']);
        $row = $this->target->query('SELECT * FROM expenses')->fetch(PDO::FETCH_ASSOC);
        $this->assertNull($row['exp_head_id']);
    }

    public function testResolvesReferencedExpenseHeadsEvenWhenAnUnrelatedDuplicateExistsElsewhere(): void
    {
        // Mirrors the real smart_school collision: TWO unrelated
        // expense_head rows elsewhere share a category ("Utilities"), but
        // nothing being migrated references either of them. Must NOT
        // throw, and the actually-referenced expense head must still
        // resolve correctly.
        $this->source->exec("INSERT INTO expense_head (id, exp_category) VALUES (500, 'Salaries'), (501, 'Utilities'), (502, 'Utilities')");
        $this->target->exec("INSERT INTO expense_head (id, exp_category, tenant_id) VALUES (1, 'Salaries', 25), (2, 'Utilities', 25), (3, 'Utilities', 25)");
        $this->source->exec("INSERT INTO expenses (id, exp_head_id, name, amount) VALUES (901, 500, 'Teacher Pay', 5000.00)");

        $merger = new MergeExpensesData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(1, $result['expenses_migrated']);
        $row = $this->target->query('SELECT * FROM expenses')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(1, (int) $row['exp_head_id']);
    }

    public function testSkipsExpensesReferencingAnUnresolvableExpenseHead(): void
    {
        $this->source->exec("INSERT INTO expenses (id, exp_head_id, name) VALUES (901, 999, 'Orphan Expense')");

        $merger = new MergeExpensesData($this->source, $this->target, 25);
        $result = $merger->run();

        $this->assertSame(0, $result['expenses_migrated']);
        $this->assertSame(1, $result['expenses_skipped']);
    }

    public function testRefusesToRunAgainIfTenantAlreadyHasExpenseRows(): void
    {
        $this->target->exec("INSERT INTO expenses (id, name, tenant_id) VALUES (1, 'Existing', 25)");
        $this->source->exec("INSERT INTO expenses (id, name) VALUES (901, 'New')");

        $merger = new MergeExpensesData($this->source, $this->target, 25);

        $threw = false;
        try {
            $merger->run();
        } catch (RuntimeException $e) {
            $threw = true;
        }

        $this->assertTrue($threw, 'Expected run() to refuse when the tenant already has expense rows');
    }
}
