<?php

namespace studioespresso\daterange\behaviors;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\errors\InvalidFieldException;
use craft\helpers\Db;
use yii\base\Behavior;
use yii\base\InvalidConfigException;

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

    public string|null $entryTypeHandle = null;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ElementQuery::EVENT_AFTER_PREPARE => 'onAfterPrepare',
        ];
    }

    public function isFuture($value, $entryTypeHandle, $includeToday = false)
    {
        $this->handle = is_array($value) ? $value[0] : $value;
        $this->includeToday = is_array($value) ? $value[1] : $includeToday;
        $this->isFuture = true;
        $this->entryTypeHandle = $entryTypeHandle;

        return $this->owner;
    }

    public function isPast($value, $entryTypeHandle, $includeToday = false)
    {
        $this->handle = is_array($value) ? $value[0] : $value;
        $this->includeToday = is_array($value) ? $value[1] : $includeToday;
        $this->isPast = true;
        $this->entryTypeHandle = $entryTypeHandle;
        return $this->owner;
    }

    public function isNotPast($value, $entryTypeHandle, $includeToday = false)
    {
        $this->handle = is_array($value) ? $value[0] : $value;
        $this->includeToday = is_array($value) ? $value[1] : $includeToday;
        $this->isNotPast = true;
        $this->entryTypeHandle = $entryTypeHandle;
        return $this->owner;
    }

    public function isOnGoing($value, $entryTypeHandle, $includeToday = false)
    {
        $this->handle = is_array($value) ? $value[0] : $value;
        $this->includeToday = is_array($value) ? $value[1] : $includeToday;
        $this->isOnGoing = true;
        $this->entryTypeHandle = $entryTypeHandle;
        return $this->owner;
    }

    public function onAfterPrepare()
    {
        if ($this->handle && !$this->entryTypeHandle) {
            throw new InvalidConfigException("entryType not specified, see the Craft 5 upgrade guide on the changes required.");
        }

        if ($this->handle && $this->entryTypeHandle) {
            $type = Craft::$app->getEntries()->getEntryTypeByHandle($this->entryTypeHandle);
            if (!$type) {
                throw new InvalidConfigException("Invalid entryType specified");
            }
            $layout = Craft::$app->getFields()->getLayoutById($type->fieldLayoutId);
            $this->field = $layout->getFieldByHandle($this->handle);
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
                        $this->field->getValueSql('start'),
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }

            if ($this->field && $this->isPast) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $this->field->getValueSql('end'),
                        date('Y-m-d'),
                        $this->includeToday ? '<=' : '<'
                    ));
            }

            if ($this->field && $this->isNotPast) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $this->field->getValueSql('end'),
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }

            if ($this->field && $this->isOnGoing) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $this->field->getValueSql('start'),
                        date('Y-m-d'),
                        $this->includeToday ? '<=' : '<'
                    ));
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $this->field->getValueSql('end'),
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }
        }
    }
}
