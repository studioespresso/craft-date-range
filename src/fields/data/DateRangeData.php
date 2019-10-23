<?php

namespace studioespresso\daterange\fields\data;

use craft\base\FieldInterface;
use craft\base\Serializable;
use craft\helpers\Json;
use studioespresso\daterange\fields\DateRangeField;
use yii\base\BaseObject;

class DateRangeData extends BaseObject implements Serializable
{

    public $start;

    public $end;

    public function __construct($value = null, $config = [] )
    {
        $this->start = $value['start'];
        $this->end = $value['end'];
        parent::__construct($config);
    }

    public function serialize()
    {
        return [$this->start, $this->end];
    }

    public static function normalize($value = null, FieldInterface $config)
    {

        if(!is_array($value)) {
            $value = Json::decode($value);
        }

        $start = $value['start'];
        $start = new \DateTime(isset($start['date']) ? $start['date'] : $start);
        if (!$config->showStartTime) {
            $start->setTime(00, 00, 00);
        }

        $end = $value['end'];
        $end = new \DateTime(isset($end['date']) ? $end['date'] : $end);
        if (!$config->showEndTime) {
            $end->setTime(00, 00, 00);
        }
        

        return [
            'start' => $start,
            'end' => $end
        ];
    }
}
