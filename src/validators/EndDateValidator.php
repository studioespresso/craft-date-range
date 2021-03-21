<?php

namespace studioespresso\daterange\validators;

use Craft;
use yii\validators\Validator;

class EndDateValidator extends Validator
{
    public function validateValue($value)
    {
        if ($value->start->format('U') > $value->end->format('U')) {
            return [Craft::t('date-range', 'End date must be after start date'), []];
        };
        return null;
    }
}