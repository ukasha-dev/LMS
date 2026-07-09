<?php

use PHPUnit\Framework\TestCase;

final class NaturalKeyIdResolverTest extends TestCase
{
    private PDO $source;
    private PDO $target;
    private NaturalKeyIdResolver $resolver;

    protected function setUp(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $admin->exec('DROP DATABASE IF EXISTS resolver_test_source');
        $admin->exec('CREATE DATABASE resolver_test_source');
        $admin->exec('DROP DATABASE IF EXISTS resolver_test_target');
        $admin->exec('CREATE DATABASE resolver_test_target');

        $this->source = new PDO('mysql:host=127.0.0.1;dbname=resolver_test_source;charset=utf8mb4', 'root', '');
        $this->source->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->target = new PDO('mysql:host=127.0.0.1;dbname=resolver_test_target;charset=utf8mb4', 'root', '');
        $this->target->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->source->exec('CREATE TABLE students (id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL)');
        $this->target->exec('CREATE TABLE students (id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL, tenant_id INT NOT NULL)');

        $this->resolver = new NaturalKeyIdResolver();
    }

    protected function tearDown(): void
    {
        $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
        $admin->exec('DROP DATABASE IF EXISTS resolver_test_source');
        $admin->exec('DROP DATABASE IF EXISTS resolver_test_target');
    }

    public function testResolvesOldIdToNewIdByMatchingNaturalKey(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (7, 'ADM-001', 25)");

        $map = $this->resolver->resolve($this->source, $this->target, 25, 'students', 'admission_no');

        $this->assertSame([101 => 7], $map);
    }

    public function testExcludesRowsWithNullOrEmptyNaturalKey(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, NULL), (102, '')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (7, NULL, 25), (8, '', 25)");

        $map = $this->resolver->resolve($this->source, $this->target, 25, 'students', 'admission_no');

        $this->assertSame([], $map);
    }

    public function testOnlyMatchesWithinTheGivenTenant(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (7, 'ADM-001', 99)");

        $map = $this->resolver->resolve($this->source, $this->target, 25, 'students', 'admission_no');

        $this->assertSame([], $map);
    }

    public function testUnmatchedSourceRowIsSimplyAbsentFromTheMap(): void
    {
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001'), (102, 'ADM-002')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (7, 'ADM-001', 25)");

        $map = $this->resolver->resolve($this->source, $this->target, 25, 'students', 'admission_no');

        $this->assertSame([101 => 7], $map);
        $this->assertArrayNotHasKey(102, $map);
    }
}
