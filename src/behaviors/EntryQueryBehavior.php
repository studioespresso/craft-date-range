<?php

namespace studioespresso\daterange\behaviors;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\errors\InvalidFieldException;
use craft\helpers\Db;
use yii\base\Behavior;

/**
 * Class EntryQueryBehavior
 *
 * @property EntryQuery $owner
 */
class EntryQueryBehavior extends Behavior
{

    public $handle;

    public $field = false;

    public $columnSuffix = '';

    public $isFuture = false;

    public $isPast = false;

    public $isNotPast = false;

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
        $this->handle = is_array($value) ? $value[0] : $value;
        $this->includeToday = is_array($value) ? $value[1] : $includeToday;
        $this->isFuture = true;
        return $this->owner;
    }

    public function isPast($value, $includeToday = false)
    {
        $this->handle = is_array($value) ? $value[0] : $value;
        $this->includeToday = is_array($value) ? $value[1] : $includeToday;
        $this->isPast = true;
        return $this->owner;
    }

    public function isNotPast($value, $includeToday = false)
    {
        $this->handle = is_array($value) ? $value[0] : $value;
        $this->includeToday = is_array($value) ? $value[1] : $includeToday;
        $this->isNotPast = true;
        return $this->owner;
    }

    public function isOnGoing($value, $includeToday = false)
    {
        $this->handle = is_array($value) ? $value[0] : $value;
        $this->includeToday = is_array($value) ? $value[1] : $includeToday;
        $this->isOnGoing = true;
        return $this->owner;
    }

    public function onAfterPrepare()
    {
        if ($this->handle) {

            $field = Craft::$app->getFields()->getFieldByHandle($this->handle);
            if (!$field) {
                throw new InvalidFieldException("Field '{$this->handle}' not found");
            }

            $this->field = $field;
            if ($this->field->columnSuffix) {
                $this->columnSuffix = '_' . $this->field->columnSuffix;
            }
        }

        if (Craft::$app->db->getIsPgsql()) {
            if ($this->field && $this->isFuture) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        '"field_' . $this->handle . $this->columnSuffix . '"::json->>\'start\'',
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }
            if ($this->field && $this->isPast) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        '"field_' . $this->handle . $this->columnSuffix . '"::json->>\'end\'',
                        date('Y-m-d'),
                        $this->includeToday ? '<=' : '<'
                    ));
            }

            if ($this->field && $this->isNotPast) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        '"field_' . $this->handle . $this->columnSuffix . '"::json->>\'end\'',
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }

            if ($this->field && $this->isOnGoing) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        '"field_' . $this->handle . $this->columnSuffix . '"::json->>\'start\'',
                        date('Y-m-d'),
                        $this->includeToday ? '<=' : '<'
                    ));
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        '"field_' . $this->handle . $this->columnSuffix . '"::json->>\'end\'',
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }
        } elseif (Craft::$app->db->getIsMysql()) {
            if ($this->field && $this->isFuture) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        "JSON_EXTRACT(field_$this->handle$this->columnSuffix, '$.start')",
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }

            if ($this->field && $this->isPast) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        "JSON_EXTRACT(field_$this->handle$this->columnSuffix, '$.end')",
                        date('Y-m-d'),
                        $this->includeToday ? '<=' : '<'
                    ));
            }

            if ($this->field && $this->isNotPast) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        "JSON_EXTRACT(field_$this->handle$this->columnSuffix, '$.end')",
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }

            if ($this->field && $this->isOnGoing) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        "JSON_EXTRACT(field_$this->handle$this->columnSuffix, '$.start')",
                        date('Y-m-d'),
                        $this->includeToday ? '<=' : '<'
                    ));
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        "JSON_EXTRACT(field_$this->handle$this->columnSuffix, '$.end')",
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }
        }
    }
}
