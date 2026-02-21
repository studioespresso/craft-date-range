<?php

namespace studioespresso\daterange\tests\unit;

use Codeception\Test\Unit;
use studioespresso\daterange\fields\data\DateRangeData;

class DateRangeDataTest extends Unit
{
    public function testConstructorSetsStartAndEnd()
    {
        $start = new \DateTime('2025-06-01 10:00:00');
        $end = new \DateTime('2025-06-02 18:00:00');

        $data = new DateRangeData(['start' => $start, 'end' => $end]);

        $this->assertSame($start, $data->start);
        $this->assertSame($end, $data->end);
    }

    public function testConstructorFallsBackEndToStartWhenEmpty()
    {
        $start = new \DateTime('2025-06-01 10:00:00');

        $data = new DateRangeData(['start' => $start, 'end' => null]);

        $this->assertSame($start, $data->start);
        $this->assertSame($start, $data->end);
    }

    public function testIsFutureReturnsTrueWhenStartIsInFuture()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('+1 year'),
            'end' => new \DateTime('+2 years'),
        ]);

        $this->assertTrue($data->isFuture);
    }

    public function testIsFutureReturnsFalseWhenStartIsInPast()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('-1 year'),
            'end' => new \DateTime('+1 year'),
        ]);

        $this->assertFalse($data->isFuture);
    }

    public function testIsPastReturnsTrueWhenEndIsInPast()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('-2 years'),
            'end' => new \DateTime('-1 year'),
        ]);

        $this->assertTrue($data->isPast);
    }

    public function testIsPastReturnsFalseWhenEndIsInFuture()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('-1 year'),
            'end' => new \DateTime('+1 year'),
        ]);

        $this->assertFalse($data->isPast);
    }

    public function testIsOngoingReturnsTrueWhenNowIsBetweenStartAndEnd()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('-1 year'),
            'end' => new \DateTime('+1 year'),
        ]);

        $this->assertTrue($data->isOngoing);
    }

    public function testIsOngoingReturnsFalseWhenInFuture()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('+1 year'),
            'end' => new \DateTime('+2 years'),
        ]);

        $this->assertFalse($data->isOngoing);
    }

    public function testIsNotPastReturnsTrueWhenEndIsInFuture()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('-1 year'),
            'end' => new \DateTime('+1 year'),
        ]);

        $this->assertTrue($data->isNotPast);
    }

    public function testIsNotPastReturnsFalseWhenEndIsInPast()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('-2 years'),
            'end' => new \DateTime('-1 year'),
        ]);

        $this->assertFalse($data->isNotPast);
    }

    public function testSerializeReturnsCorrectArrayStructure()
    {
        $start = new \DateTime('2025-06-01 10:00:00');
        $end = new \DateTime('2025-06-02 18:00:00');

        $data = new DateRangeData(['start' => $start, 'end' => $end]);
        $serialized = $data->serialize();

        $this->assertIsArray($serialized);
        $this->assertCount(2, $serialized);
        $this->assertSame($start, $serialized[0]);
        $this->assertSame($end, $serialized[1]);
    }
}
