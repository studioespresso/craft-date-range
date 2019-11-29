<?php

namespace studioespresso\daterange\fields\data;

use craft\base\FieldInterface;
use craft\base\Serializable;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use studioespresso\daterange\fields\DateRangeField;
use yii\base\BaseObject;
use yii\i18n\Formatter;

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
        $this->isFuture = $this->getIsFuture();
        $this->isOngoing = $this->getIsOngoing();
        $this->isPast = $this->getIsPast();
        parent::__construct($config);
    }

    public function serialize()
    {
        return [$this->start, $this->end];
    }

    public function getFormatted($format = 'd/m/Y', $seperator = ' - ')
    {

        $string = '';
        $string .= $this->start->format($format);
        $string .= $seperator;
        $string .= $this->end->format($format);
        return $string;
    }


    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return bool
     * @throws \Exception
     */
    public function getIsFuture()
    {
        $now = new \DateTime();
        if ($this->start->format('U') > $now->format('U')) {
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
    public function getIsOngoing()
    {

        $now = new \DateTime();
        if (
            $this->start->format('U') < $now->format('U')
            && $this->end->format('U') > $now->format('U')
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
    public function getIsPast()
    {
        $now = new \DateTime();
        if ($this->end->format('U') < $now->format('U')) {
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
        $start = DateTimeHelper::toDateTime($start);

        $end = $value['end'];
        $end = DateTimeHelper::toDateTime($end);

        return [
            'start' => $start,
            'end' => $end
        ];
    }
}
