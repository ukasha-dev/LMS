<?php

use PHPUnit\Framework\TestCase;

final class PilotAccessGateTest extends TestCase
{
    public function testDevelopmentIsAllowed(): void
    {
        $this->assertTrue(PilotAccessGate::isAllowed('development'));
    }

    public function testProductionIsNotAllowed(): void
    {
        $this->assertFalse(PilotAccessGate::isAllowed('production'));
    }

    public function testTestingIsNotAllowed(): void
    {
        $this->assertFalse(PilotAccessGate::isAllowed('testing'));
    }

    public function testEmptyStringIsNotAllowed(): void
    {
        $this->assertFalse(PilotAccessGate::isAllowed(''));
    }
}
