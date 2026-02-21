<?php

namespace studioespresso\daterange\tests\unit;

use Codeception\Test\Unit;
use studioespresso\daterange\fields\data\DateRangeData;
use studioespresso\daterange\validators\EndDateValidator;

class EndDateValidatorTest extends Unit
{
    private EndDateValidator $validator;

    protected function _before()
    {
        parent::_before();
        $this->validator = new EndDateValidator();
    }

    public function testEndAfterStartPasses()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-01-01 10:00:00'),
            'end' => new \DateTime('2025-01-02 10:00:00'),
        ]);

        $result = $this->validator->validateValue($data);
        $this->assertNull($result);
    }

    public function testEndBeforeStartReturnsError()
    {
        $data = new DateRangeData([
            'start' => new \DateTime('2025-01-02 10:00:00'),
            'end' => new \DateTime('2025-01-01 10:00:00'),
        ]);

        $result = $this->validator->validateValue($data);
        $this->assertIsArray($result);
        $this->assertStringContainsString('End date must be after start date', $result[0]);
    }

    public function testEqualDatesPasses()
    {
        $date = new \DateTime('2025-01-01 10:00:00');
        $data = new DateRangeData([
            'start' => clone $date,
            'end' => clone $date,
        ]);

        $result = $this->validator->validateValue($data);
        $this->assertNull($result);
    }
}
