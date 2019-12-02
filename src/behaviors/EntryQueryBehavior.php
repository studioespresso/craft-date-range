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

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ElementQuery::EVENT_AFTER_PREPARE => 'onAfterPrepare',
        ];
    }

    public function isFuture($value)
    {
        $this->field = $value;
        $this->isFuture = true;
        return $this->owner;
    }

    public function isPast($value)
    {
        $this->field = $value;
        $this->isPast = true;
        return $this->owner;
    }

    public function isOnGoing($value)
    {
        $this->field = $value;
        $this->isOnGoing = true;
        return $this->owner;
    }

    public function onAfterPrepare()
    {
        if ($this->field && $this->isFuture) {
            $this->owner->subQuery
                ->andWhere(Db::parseDateParam(
                    "JSON_EXTRACT(field_$this->field, '$.end')",
                    date('Y-m-d'),
                    '>'
                ));
        }

        if ($this->field && $this->isPast) {
            $this->owner->subQuery
                ->andWhere(Db::parseDateParam(
                    "JSON_EXTRACT(field_$this->field, '$.end')",
                    date('Y-m-d'),
                    '<'
                ));
        }

        if ($this->field && $this->isOnGoing) {
            $this->owner->subQuery
                ->andWhere(Db::parseDateParam(
                    "JSON_EXTRACT(field_$this->field, '$.start')",
                    date('Y-m-d'),
                    '<'
                ));
            $this->owner->subQuery
                ->andWhere(Db::parseDateParam(
                    "JSON_EXTRACT(field_$this->field, '$.end')",
                    date('Y-m-d'),
                    '>'
                ));
        }
    }
}
