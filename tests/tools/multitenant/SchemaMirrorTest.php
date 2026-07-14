<?php

use PHPUnit\Framework\TestCase;

final class SchemaMirrorTest extends TestCase
{
    private PDO $source;
    private PDO $target;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS schema_mirror_test_source');
        $admin->exec('CREATE DATABASE schema_mirror_test_source');
        $admin->exec('DROP DATABASE IF EXISTS schema_mirror_test_target');
        $admin->exec('CREATE DATABASE schema_mirror_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=schema_mirror_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=schema_mirror_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->source->exec(
            'CREATE TABLE widgets ('
            . 'id INT AUTO_INCREMENT PRIMARY KEY, '
            . 'name VARCHAR(60) NOT NULL, '
            . 'notes TEXT DEFAULT NULL, '
            . 'price FLOAT(10,2) DEFAULT NULL, '
            . "status VARCHAR(20) NOT NULL DEFAULT 'active', "
            . 'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, '
            . 'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            . ')'
        );
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS schema_mirror_test_source');
        $admin->exec('DROP DATABASE IF EXISTS schema_mirror_test_target');
    }

    public function testGeneratedDdlCreatesAWorkingTargetTableWithTenantIdAppended(): void
    {
        $mirror = new SchemaMirror($this->source);
        $sql = $mirror->generateCreateTableSql('schema_mirror_test_source', 'widgets');

        $this->target->exec($sql);

        $columns = $this->target->query('DESCRIBE widgets')->fetchAll(PDO::FETCH_ASSOC);
        $byName = [];
        foreach ($columns as $col) {
            $byName[$col['Field']] = $col;
        }

        $this->assertArrayHasKey('tenant_id', $byName, 'tenant_id column must be appended');
        $this->assertSame('NO', $byName['tenant_id']['Null']);
        $this->assertSame('int(11)', $byName['tenant_id']['Type']);

        $this->assertSame('PRI', $byName['id']['Key']);
        $this->assertSame('auto_increment', $byName['id']['Extra']);

        $this->assertSame('varchar(60)', $byName['name']['Type']);
        $this->assertSame('NO', $byName['name']['Null']);

        $this->assertSame('text', $byName['notes']['Type']);
        $this->assertSame('YES', $byName['notes']['Null']);

        $this->assertSame('float(10,2)', $byName['price']['Type']);

        $this->assertSame("active", trim($byName['status']['Default'], "'"));

        $this->assertSame('current_timestamp()', $byName['created_at']['Default']);

        $this->assertStringContainsString('on update current_timestamp()', $byName['updated_at']['Extra']);
    }

    public function testInsertingARealRowIntoTheGeneratedTableWorks(): void
    {
        $mirror = new SchemaMirror($this->source);
        $sql = $mirror->generateCreateTableSql('schema_mirror_test_source', 'widgets');
        $this->target->exec($sql);

        $this->target->exec("INSERT INTO widgets (name, tenant_id) VALUES ('Widget A', 25)");

        $row = $this->target->query('SELECT * FROM widgets')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Widget A', $row['name']);
        $this->assertSame(25, (int) $row['tenant_id']);
        $this->assertSame('active', $row['status']);
    }

    public function testThrowsOnCompositePrimaryKeyInsteadOfSilentlyEmittingAWrongSingleColumnKey(): void
    {
        // MySQL/MariaDB reports column_key='PRI' on EVERY column of a
        // multi-column primary key -- a naive "last PRI column wins"
        // implementation would silently create a table with a wrong,
        // narrower uniqueness constraint instead of failing loudly.
        $this->source->exec(
            'CREATE TABLE class_teachers ('
            . 'student_id INT NOT NULL, '
            . 'class_id INT NOT NULL, '
            . 'PRIMARY KEY (student_id, class_id)'
            . ')'
        );

        $mirror = new SchemaMirror($this->source);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/composite PRIMARY KEY/');

        $mirror->generateCreateTableSql('schema_mirror_test_source', 'class_teachers');
    }
}
