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

        $this->source->exec('CREATE TABLE students_with_active (id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL, is_active VARCHAR(10) DEFAULT NULL)');
        $this->target->exec('CREATE TABLE students_with_active (id INT AUTO_INCREMENT PRIMARY KEY, admission_no VARCHAR(100) DEFAULT NULL, is_active VARCHAR(10) DEFAULT NULL, tenant_id INT NOT NULL)');

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

    public function testThrowsWhenSourceHasDuplicateNaturalKeyWithDifferentIds(): void
    {
        // Two DIFFERENT source rows share the same admission_no — the
        // resolver cannot know which source id "ADM-001" should refer to.
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001'), (102, 'ADM-001')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (7, 'ADM-001', 25)");

        $this->expectException(RuntimeException::class);

        $this->resolver->resolve($this->source, $this->target, 25, 'students', 'admission_no');
    }

    public function testThrowsWhenTargetHasDuplicateNaturalKeyWithDifferentIds(): void
    {
        // Same shape as above, but the ambiguity lives on the TARGET side:
        // two different rows for the same tenant share the same admission_no.
        $this->source->exec("INSERT INTO students (id, admission_no) VALUES (101, 'ADM-001')");
        $this->target->exec("INSERT INTO students (id, admission_no, tenant_id) VALUES (7, 'ADM-001', 25), (8, 'ADM-001', 25)");

        $this->expectException(RuntimeException::class);

        $this->resolver->resolve($this->source, $this->target, 25, 'students', 'admission_no');
    }

    public function testDuplicateSourceKeyWithExactlyOneActiveRowResolvesToTheActiveOne(): void
    {
        // Real school data: a duplicate admission_no gets "corrected" by
        // deactivating the stale row, not deleting it. The active row is
        // the genuine record and should resolve silently -- no exception.
        $this->source->exec("INSERT INTO students_with_active (id, admission_no, is_active) VALUES (101, 'ADM-001', 'no'), (102, 'ADM-001', 'yes')");
        $this->target->exec("INSERT INTO students_with_active (id, admission_no, is_active, tenant_id) VALUES (7, 'ADM-001', 'yes', 25)");

        $map = $this->resolver->resolve($this->source, $this->target, 25, 'students_with_active', 'admission_no');

        $this->assertSame([102 => 7], $map);
    }

    public function testDuplicateTargetKeyWithExactlyOneActiveRowResolvesToTheActiveOne(): void
    {
        $this->source->exec("INSERT INTO students_with_active (id, admission_no, is_active) VALUES (101, 'ADM-001', 'yes')");
        $this->target->exec("INSERT INTO students_with_active (id, admission_no, is_active, tenant_id) VALUES (7, 'ADM-001', 'no', 25), (8, 'ADM-001', 'yes', 25)");

        $map = $this->resolver->resolve($this->source, $this->target, 25, 'students_with_active', 'admission_no');

        $this->assertSame([101 => 8], $map);
    }

    public function testDuplicateKeyWithNoActiveRowsIsDroppedRatherThanThrown(): void
    {
        // Two dead/inactive duplicates (e.g. leftover test rows) -- neither
        // is a live record, so there is nothing dangerous about simply not
        // resolving this key. Must not throw and must not appear in the map.
        $this->source->exec("INSERT INTO students_with_active (id, admission_no, is_active) VALUES (101, 'ADM-001', 'no'), (102, 'ADM-001', 'no')");
        $this->target->exec("INSERT INTO students_with_active (id, admission_no, is_active, tenant_id) VALUES (7, 'ADM-001', 'no', 25)");

        $map = $this->resolver->resolve($this->source, $this->target, 25, 'students_with_active', 'admission_no');

        $this->assertSame([], $map);
    }

    public function testDuplicateKeyWithMultipleActiveRowsStillThrows(): void
    {
        // Two DIFFERENT active rows sharing a key is genuine, dangerous
        // ambiguity -- the is_active tiebreak must not paper over this.
        $this->source->exec("INSERT INTO students_with_active (id, admission_no, is_active) VALUES (101, 'ADM-001', 'yes'), (102, 'ADM-001', 'yes')");
        $this->target->exec("INSERT INTO students_with_active (id, admission_no, is_active, tenant_id) VALUES (7, 'ADM-001', 'yes', 25)");

        $this->expectException(RuntimeException::class);

        $this->resolver->resolve($this->source, $this->target, 25, 'students_with_active', 'admission_no');
    }
}
