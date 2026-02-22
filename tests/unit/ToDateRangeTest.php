<?php

namespace studioespresso\daterange\tests\unit;

use Codeception\Test\Unit;
use studioespresso\daterange\DateRange;

class ToDateRangeTest extends Unit
{
    public function testParsesStringWithArrowSeparator()
    {
        $result = DateRange::toDateRange('2025-06-01 => 2025-06-30');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('end', $result);
        $this->assertInstanceOf(\DateTime::class, $result['start']);
        $this->assertInstanceOf(\DateTime::class, $result['end']);
        $this->assertLessThanOrEqual($result['end']->getTimestamp(), $result['start']->getTimestamp());
    }

    public function testParsesStringWithoutSpacesAroundArrow()
    {
        $result = DateRange::toDateRange('2025-06-01=>2025-06-30');

        $this->assertNotNull($result);
        $this->assertArrayHasKey('start', $result);
        $this->assertArrayHasKey('end', $result);
    }

    public function testParsesSingleDateStringReturnsSameStartAndEnd()
    {
        $result = DateRange::toDateRange('2025-06-15');

        $this->assertNotNull($result);
        $this->assertEquals(
            $result['start']->format('Y-m-d'),
            $result['end']->format('Y-m-d')
        );
    }

    public function testParsesAssociativeArray()
    {
        $result = DateRange::toDateRange(['start' => '2025-06-01', 'end' => '2025-06-30']);

        $this->assertNotNull($result);
        $this->assertInstanceOf(\DateTime::class, $result['start']);
        $this->assertInstanceOf(\DateTime::class, $result['end']);
        $this->assertLessThan($result['end']->getTimestamp(), $result['start']->getTimestamp());
    }

    public function testArrayWithOnlyStartUsesStartAsEnd()
    {
        $result = DateRange::toDateRange(['start' => '2025-06-15']);

        $this->assertNotNull($result);
        $this->assertEquals(
            $result['start']->getTimestamp(),
            $result['end']->getTimestamp()
        );
    }

    public function testAcceptsDateTimeObject()
    {
        $date = new \DateTime('2025-06-15');
        $result = DateRange::toDateRange($date);

        $this->assertNotNull($result);
        $this->assertEquals('2025-06-15', $result['start']->format('Y-m-d'));
        $this->assertEquals('2025-06-15', $result['end']->format('Y-m-d'));
    }

    public function testAcceptsUnixTimestamp()
    {
        $timestamp = (new \DateTime('2025-06-15'))->getTimestamp();
        $result = DateRange::toDateRange($timestamp);

        $this->assertNotNull($result);
        $this->assertInstanceOf(\DateTime::class, $result['start']);
    }

    public function testReturnsNullWhenEndIsBeforeStart()
    {
        $result = DateRange::toDateRange('2025-06-30 => 2025-06-01');

        $this->assertNull($result);
    }

    public function testStartIsBeforeEndInParsedRange()
    {
        $result = DateRange::toDateRange('2025-01-01 => 2025-12-31');

        $this->assertNotNull($result);
        $this->assertLessThan($result['end']->getTimestamp(), $result['start']->getTimestamp());
    }
}
