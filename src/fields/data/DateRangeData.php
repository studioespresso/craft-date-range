<?php

namespace studioespresso\daterange\fields\data;

use Craft;
use craft\base\FieldInterface;
use craft\base\Serializable;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use craft\i18n\Locale;
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

    public $isNotPast;


    public function __construct($value = null, $config = [])
    {
        $this->start = $value['start'];
        $this->end = $value['end'] ?: $value['start'];
        $this->isFuture = $this->getIsFuture();
        $this->isOngoing = $this->getIsOngoing();
        $this->isPast = $this->getIsPast();
        $this->isNotPast = $this->getIsNotPast();
        parent::__construct($config);
    }

    public function serialize()
    {
        return [$this->start, $this->end];
    }

    public function getFormatted($format = 'd/m/Y', $seperator = ' - ', $locale = null)
    {
        if (is_array($format)) {
            if (isset($format['date'])) {
                $dateFormat = $format['date'];
            }
            if (isset($format['time'])) {
                $timeFormat = $format['time'];
            }
            $format = ($dateFormat ? $dateFormat : '') . ' ' . ($timeFormat ? $timeFormat : '');
        } else {
            $format = $format;
        }
        $string = '';
        $formatter = $locale ? (new Locale($locale))->getFormatter() : Craft::$app->getFormatter();

        if($this->start->format('U') === $this->end->format('U')) {
           $string = $formatter->asDate($this->start, "php:$format");
        }  elseif ($this->start->format('dmy') === $this->end->format('dmy') && isset($timeFormat)) {
            $string .= $formatter->asDate($this->start, "php:$dateFormat");
            $string .= " ";
            $string .= $formatter->asTime($this->start, "php:$timeFormat");
            $string .= " " . trim($seperator) . " ";
            $string .= $formatter->asTime($this->end, "php:$timeFormat");
        } else {
            $string .= $formatter->asDate($this->start, "php:$format");
            $string .= " " . trim($seperator) . " ";
            $string .= $formatter->asDate($this->end, "php:$format");
        }
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

    /**
     * @param \DateTime $start
     * @param \DateTime $end
     * @return bool
     * @throws \Exception
     */
    public function getIsNotPast()
    {
        $now = new \DateTime();
        if ($this->end->format('U') > $now->format('U')) {
            return true;
        }
        return false;
    }

    public static function normalize($value = null, FieldInterface $config)
    {
        if (!is_array($value)) {
            $value = Json::decode($value);
        }

        if ((isset($value['start']['date']) && !$value['start']['date'])
        ) {
            return false;
        } else {
            if (isset($value['end']['date']) && !$value['end']['date']) {
                $value['end']['date'] = $value['start']['date'];
            }
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
