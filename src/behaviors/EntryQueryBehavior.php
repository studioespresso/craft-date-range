<?php

namespace studioespresso\daterange\behaviors;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
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

    public function isFuture($value, string|bool|null $entryTypeHandle = null, bool $includeToday = false)
    {
        $value = $this->parseArgumentValue($value, $entryTypeHandle, $includeToday);

        $this->handle = $value['handle'];
        $this->isFuture = true;
        $this->entryTypeHandle = $value['entryTypeHandle'];
        $this->includeToday = $value['includeToday'];

        return $this->owner;
    }

    public function isPast($value, string|bool|null $entryTypeHandle = null, $includeToday = false)
    {
        $value = $this->parseArgumentValue($value, $entryTypeHandle, $includeToday);

        $this->handle = $value['handle'];
        $this->isPast = true;
        $this->entryTypeHandle = $value['entryTypeHandle'];
        $this->includeToday = $value['includeToday'];
        return $this->owner;
    }

    public function isNotPast($value, string|bool|null $entryTypeHandle = null, $includeToday = false)
    {
        $value = $this->parseArgumentValue($value, $entryTypeHandle, $includeToday);

        $this->handle = $value['handle'];
        $this->isNotPast = true;
        $this->entryTypeHandle = $value['entryTypeHandle'];
        $this->includeToday = $value['includeToday'];
        return $this->owner;
    }

    public function isOnGoing($value, string|bool|null $entryTypeHandle = null, $includeToday = false)
    {
        $value = $this->parseArgumentValue($value, $entryTypeHandle, $includeToday);

        $this->handle = $value['handle'];
        $this->isOnGoing = true;
        $this->entryTypeHandle = $value['entryTypeHandle'];
        $this->includeToday = $value['includeToday'];
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
            /** @var \craft\base\FieldInterface|null $field */
            $field = $this->field;
            if ($field && $this->isFuture) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('start'),
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }

            if ($field && $this->isPast) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('end'),
                        date('Y-m-d'),
                        $this->includeToday ? '<=' : '<'
                    ));
            }

            if ($field && $this->isNotPast) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('end'),
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }

            if ($field && $this->isOnGoing) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('start'),
                        date('Y-m-d'),
                        $this->includeToday ? '<=' : '<'
                    ));
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('end'),
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }
        } elseif (Craft::$app->db->getIsMysql()) {
            /** @var \craft\base\FieldInterface|null $field */
            $field = $this->field;
            if ($field && $this->isFuture) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('start'),
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }

            if ($field && $this->isPast) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('end'),
                        date('Y-m-d'),
                        $this->includeToday ? '<=' : '<'
                    ));
            }

            if ($field && $this->isNotPast) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('end'),
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }

            if ($field && $this->isOnGoing) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('start'),
                        date('Y-m-d'),
                        $this->includeToday ? '<=' : '<'
                    ));
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('end'),
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    ));
            }
        }
    }

    protected function parseArgumentValue(
        string|array $value,
        string|bool|null $entryTypeHandle = null,
        $includeToday = false,
    ): array {
        $handle = null;

        if (is_array($value)) {
            $handle = $value[0] ?? null;
            $arg2 = $value[1] ?? null;
            if (is_string($arg2)) {
                $entryTypeHandle = $arg2;
            } elseif ($arg2 !== null) {
                $includeToday = $arg2;
            }
        } else {
            $handle = $value;
        }

        return [
            'handle' => $handle,
            'entryTypeHandle' => $entryTypeHandle,
            'includeToday' => $includeToday,
        ];
    }
}
