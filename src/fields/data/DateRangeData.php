<?php

namespace studioespresso\daterange\fields\data;

use craft\base\FieldInterface;
use craft\base\Serializable;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use studioespresso\daterange\fields\DateRangeField;
use yii\base\BaseObject;

class DateRangeData extends BaseObject implements Serializable
{

    public $start;

    public $end;

    public $isFuture;

    public $isOngoing;

    public $isPast;


    public function __construct($value = null, $config = [])
    {
        $this->start = $value['start'];
        $this->end = $value['end'];
        $this->isFuture = $this->getIsFuture($this->start, $this->end);
        $this->isOngoing = $this->getIsOngoing($this->start, $this->end);
        $this->isPast = $this->getIsPast($this->start, $this->end);
        parent::__construct($config);
    }

    public function serialize()
    {
        return [$this->start, $this->end];
    }


    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return bool
     * @throws \Exception
     */
    public function getIsFuture(\DateTime $start, \DateTime $end)
    {
        $now = new \DateTime();
        if ($start->format('U') > $now->format('U')) {
            return true;
        }
        return false;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return bool
     * @throws \Exception
     */
    public function getIsOngoing(\DateTime $start, \DateTime $end)
    {
        $now = new \DateTime();
        if (
            $start->format('U') < $now->format('U')
            && $end->format('U') > $now->format('U')
        ) {

            return true;
        }
        return false;
    }

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return bool
     * @throws \Exception
     */
    public function getIsPast(\DateTime $start, \DateTime $end)
    {
        $now = new \DateTime();
        if ($end->format('U') < $now->format('U')) {
            return true;
        }
        return false;
    }

    public static function normalize($value = null, FieldInterface $config)
    {
        if (!is_array($value)) {
            $value = Json::decode($value);
        }

        if ((isset($value['start']['date']) && !$value['start']['date']) ||
            (isset($value['end']['date']) && !$value['end']['date'])
        ) {
            return false;
        }

        $start = $value['start'];
        if (!$config->showStartTime) {
            $start = new \DateTime(isset($start['date']) ? $start['date'] : $start);
            $start->setTime(00, 00, 00);
        } else {
            $start = DateTimeHelper::toDateTime($start);
        }

        $end = $value['end'];
        if (!$config->showEndTime) {
            $end = new \DateTime(isset($end['date']) ? $end['date'] : $end);
            $end->setTime(00, 00, 00);
        } else {
            $end = DateTimeHelper::toDateTime($end);
        }

        return [
            'start' => $start,
            'end' => $end
        ];
    }
}
