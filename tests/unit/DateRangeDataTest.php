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

    // ---- startsAfterDate ----

    public function testStartsAfterDateReturnsTrueWhenStartIsAfterDate()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-15'),
            'end' => new \DateTime('2025-06-30'),
        ]);

        $this->assertTrue($data->startsAfterDate('2025-06-01'));
    }

    public function testStartsAfterDateReturnsFalseWhenStartIsBeforeDate()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01'),
            'end' => new \DateTime('2025-06-30'),
        ]);

        $this->assertFalse($data->startsAfterDate('2025-06-15'));
    }

    public function testStartsAfterDateReturnsFalseWhenStartEqualsDate()
    {
        $start = new \DateTime('2025-06-15 00:00:00');
        $data = new DateRangeData([
            'start' => $start,
            'end' => new \DateTime('2025-06-30'),
        ]);

        $this->assertFalse($data->startsAfterDate($start));
    }

    // ---- endsBeforeDate ----

    public function testEndsBeforeDateReturnsTrueWhenEndIsBeforeDate()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01'),
            'end' => new \DateTime('2025-06-15'),
        ]);

        $this->assertTrue($data->endsBeforeDate('2025-06-30'));
    }

    public function testEndsBeforeDateReturnsFalseWhenEndIsAfterDate()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01'),
            'end' => new \DateTime('2025-06-30'),
        ]);

        $this->assertFalse($data->endsBeforeDate('2025-06-15'));
    }

    public function testEndsBeforeDateReturnsFalseWhenEndEqualsDate()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01'),
            'end' => new \DateTime('2025-06-15 00:00:00'),
        ]);

        $this->assertFalse($data->endsBeforeDate('2025-06-15 00:00:00'));
    }

    // ---- isDuringDate ----

    public function testIsDuringDateReturnsTrueWhenDateFallsWithinRange()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01'),
            'end' => new \DateTime('2025-06-30'),
        ]);

        $this->assertTrue($data->isDuringDate('2025-06-15'));
    }

    public function testIsDuringDateReturnsTrueWhenRangesOverlap()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01'),
            'end' => new \DateTime('2025-06-30'),
        ]);

        $this->assertTrue($data->isDuringDate('2025-06-15 => 2025-07-15'));
    }

    public function testIsDuringDateReturnsTrueOnBoundary()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01 00:00:00'),
            'end' => new \DateTime('2025-06-30 00:00:00'),
        ]);

        $this->assertTrue($data->isDuringDate('2025-06-30 00:00:00'));
    }

    public function testIsDuringDateReturnsFalseWhenNoOverlap()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01'),
            'end' => new \DateTime('2025-06-15'),
        ]);

        $this->assertFalse($data->isDuringDate('2025-07-01 => 2025-07-31'));
    }

    public function testIsDuringDateAcceptsArrayFormat()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01'),
            'end' => new \DateTime('2025-06-30'),
        ]);

        $this->assertTrue($data->isDuringDate(['start' => '2025-06-10', 'end' => '2025-06-20']));
    }

    // ---- isNotDuringDate ----

    public function testIsNotDuringDateReturnsTrueWhenNoOverlap()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01'),
            'end' => new \DateTime('2025-06-15'),
        ]);

        $this->assertTrue($data->isNotDuringDate('2025-07-01 => 2025-07-31'));
    }

    public function testIsNotDuringDateReturnsFalseWhenOverlapping()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01'),
            'end' => new \DateTime('2025-06-30'),
        ]);

        $this->assertFalse($data->isNotDuringDate('2025-06-15'));
    }

    public function testIsNotDuringDateReturnsFalseWhenDateOnBoundary()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01 00:00:00'),
            'end' => new \DateTime('2025-06-30 00:00:00'),
        ]);

        // Boundary: query date equals end date — isDuring uses <=/>= so this overlaps
        $this->assertFalse($data->isNotDuringDate('2025-06-30 00:00:00'));
    }

    public function testIsNotDuringDateIsInverseOfIsDuringDate()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-06-01'),
            'end' => new \DateTime('2025-06-30'),
        ]);

        $testDate = '2025-06-15';
        $this->assertNotEquals(
            $data->isDuringDate($testDate),
            $data->isNotDuringDate($testDate)
        );
    }

    // ---- serialize ----

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
