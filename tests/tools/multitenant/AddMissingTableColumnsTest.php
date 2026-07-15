<?php

use PHPUnit\Framework\TestCase;

final class AddMissingTableColumnsTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS add_missing_columns_test_source');
        $admin->exec('CREATE DATABASE add_missing_columns_test_source');
        $admin->exec('DROP DATABASE IF EXISTS add_missing_columns_test_target');
        $admin->exec('CREATE DATABASE add_missing_columns_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=add_missing_columns_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=add_missing_columns_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Source (legacy) has the real, full column set including several
        // NOT NULL columns with no default -- the kind of column that would
        // fail an ALTER against a populated table if copied verbatim. Also
        // has a literal `tenant_id` column, which must never be copied --
        // the target's own tenant_id is the multi-tenant one, not this.
        $this->source->exec(
            'CREATE TABLE widgets ('
            . 'id INT AUTO_INCREMENT PRIMARY KEY, '
            . 'tenant_id INT NOT NULL, '
            . 'name VARCHAR(60) NOT NULL, '
            . 'blood_group VARCHAR(20) NOT NULL, '
            . 'notes TEXT DEFAULT NULL'
            . ')'
        );

        // Target (school_saas-shaped) already has id/name plus a real,
        // pre-existing row -- exactly the "populated table" scenario this
        // tool exists for.
        $this->target->exec(
            'CREATE TABLE widgets ('
            . 'id INT AUTO_INCREMENT PRIMARY KEY, '
            . 'tenant_id INT NOT NULL, '
            . 'name VARCHAR(60) NOT NULL'
            . ')'
        );
        $this->target->exec("INSERT INTO widgets (tenant_id, name) VALUES (25, 'Existing Widget')");
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS add_missing_columns_test_source');
        $admin->exec('DROP DATABASE IF EXISTS add_missing_columns_test_target');
    }

    public function testGeneratesAlterStatementsOnlyForMissingColumnsAndSkipsTenantId(): void
    {
        $tool = new AddMissingTableColumns($this->source, $this->target);
        $statements = $tool->generateAlterStatements('add_missing_columns_test_source', 'add_missing_columns_test_target', 'widgets');

        $this->assertCount(2, $statements, 'only blood_group and notes should be added -- id/name already exist on the target, and the source tenant_id must be skipped entirely');
        $this->assertStringContainsString('`blood_group`', implode(' ', $statements));
        $this->assertStringContainsString('`notes`', implode(' ', $statements));
        $this->assertStringNotContainsString('`id`', implode(' ', $statements));
        $this->assertStringNotContainsString('`name`', implode(' ', $statements));
    }

    public function testAddedColumnsAreAlwaysNullableRegardlessOfSourceNotNullConstraint(): void
    {
        $tool = new AddMissingTableColumns($this->source, $this->target);
        $tool->apply('add_missing_columns_test_source', 'add_missing_columns_test_target', 'widgets');

        $columns = $this->target->query('DESCRIBE widgets')->fetchAll(PDO::FETCH_ASSOC);
        $byName = [];
        foreach ($columns as $col) {
            $byName[$col['Field']] = $col;
        }

        $this->assertArrayHasKey('blood_group', $byName);
        $this->assertSame('YES', $byName['blood_group']['Null'], 'source NOT NULL must not be copied onto a populated target table');
        $this->assertSame('varchar(20)', $byName['blood_group']['Type']);
    }

    public function testExistingRowSurvivesTheAlterWithNewColumnsNullNotErrored(): void
    {
        $tool = new AddMissingTableColumns($this->source, $this->target);
        $tool->apply('add_missing_columns_test_source', 'add_missing_columns_test_target', 'widgets');

        $row = $this->target->query('SELECT * FROM widgets WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Existing Widget', $row['name']);
        $this->assertSame(25, (int) $row['tenant_id']);
        $this->assertNull($row['blood_group']);
        $this->assertNull($row['notes']);
    }

    public function testRunningTwiceIsIdempotentAndAddsNothingTheSecondTime(): void
    {
        $tool = new AddMissingTableColumns($this->source, $this->target);
        $firstRun = $tool->apply('add_missing_columns_test_source', 'add_missing_columns_test_target', 'widgets');
        $secondRun = $tool->apply('add_missing_columns_test_source', 'add_missing_columns_test_target', 'widgets');

        $this->assertSame(2, $firstRun);
        $this->assertSame(0, $secondRun);
    }
}
