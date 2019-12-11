<?php


namespace studioespresso\daterange\behaviors;

use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\helpers\Db;
use yii\base\Behavior;

/**
 * Class EntryQueryBehavior
 *
 * @property EntryQuery $owner
 */
class EntryQueryBehavior extends Behavior
{

    public $field = false;

    public $isFuture = false;

    public $isPast = false;

    public $isOnGoing = false;

    public $includeToday;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ElementQuery::EVENT_AFTER_PREPARE => 'onAfterPrepare',
        ];
    }

    public function isFuture($value, $includeToday = false)
    {
        $this->field = $value;
        $this->includeToday = $includeToday;
        $this->isFuture = true;
        return $this->owner;
    }

    public function isPast($value, $includeToday = false)
    {
        $this->field = $value;
        $this->includeToday = $includeToday;
        $this->isPast = true;
        return $this->owner;
    }

    public function isOnGoing($value, $includeToday = false)
    {
        $this->field = $value;
        $this->includeToday = $includeToday;
        $this->isOnGoing = true;
        return $this->owner;
    }

    public function onAfterPrepare()
    {
        if ($this->field && $this->isFuture) {
            $this->owner->subQuery
                ->andWhere(Db::parseDateParam(
                    "JSON_EXTRACT(field_$this->field, '$.start')",
                    date('Y-m-d'),
                    $this->includeToday ? '>=' : '>'
                ));
        }

        if ($this->field && $this->isPast) {
            $this->owner->subQuery
                ->andWhere(Db::parseDateParam(
                    "JSON_EXTRACT(field_$this->field, '$.end')",
                    date('Y-m-d'),
                    $this->includeToday ? '<=' : '<'
                ));
        }

        if ($this->field && $this->isOnGoing) {
            $this->owner->subQuery
                ->andWhere(Db::parseDateParam(
                    "JSON_EXTRACT(field_$this->field, '$.start')",
                    date('Y-m-d'),
                    $this->includeToday ? '<=' : '<'
                ));
            $this->owner->subQuery
                ->andWhere(Db::parseDateParam(
                    "JSON_EXTRACT(field_$this->field, '$.end')",
                    date('Y-m-d'),
                    $this->includeToday ? '>=' : '>'
                ));
        }
    }
}
