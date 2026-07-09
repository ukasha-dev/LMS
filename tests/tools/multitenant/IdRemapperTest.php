<?php

use PHPUnit\Framework\TestCase;

final class IdRemapperTest extends TestCase
{
    public function testRemapsSequentiallyStartingFromGivenId(): void
    {
        $remapper = new IdRemapper(1000);
        $this->assertSame(1000, $remapper->remapId(5));
        $this->assertSame(1001, $remapper->remapId(6));
    }

    public function testSameOldIdAlwaysReturnsSameNewId(): void
    {
        $remapper = new IdRemapper(1000);
        $first = $remapper->remapId(42);
        $second = $remapper->remapId(42);
        $this->assertSame($first, $second);
    }

    public function testHasMappingReflectsWhetherIdWasRemapped(): void
    {
        $remapper = new IdRemapper(1000);
        $this->assertFalse($remapper->hasMapping(7));
        $remapper->remapId(7);
        $this->assertTrue($remapper->hasMapping(7));
    }

    public function testGetMappingReturnsNullForUnknownId(): void
    {
        $remapper = new IdRemapper(1000);
        $this->assertNull($remapper->getMapping(999));
    }

    public function testCountTracksNumberOfDistinctIdsRemapped(): void
    {
        $remapper = new IdRemapper(1000);
        $remapper->remapId(1);
        $remapper->remapId(2);
        $remapper->remapId(1);
        $this->assertSame(2, $remapper->count());
    }
}
