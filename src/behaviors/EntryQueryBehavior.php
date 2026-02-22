<?php

namespace studioespresso\daterange\behaviors;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use DateTimeInterface;
use studioespresso\daterange\DateRange;
use yii\base\Behavior;
use yii\base\Component;
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

    public $startsAfterDate = null;

    public $endsBeforeDate = null;

    public $isDuringDate = null;

    public $isNotDuringDate = null;

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

    public function startsAfterDate(
        string|array $value,
        string|DateTimeInterface|int $date = null,
        string|bool $entryTypeHandle = null
    ): Component|null {
        $value = $this->parseDateArgumentValue($value, $date, $entryTypeHandle);

        $this->handle = $value['handle'];
        $this->startsAfterDate = $value['date'];
        $this->entryTypeHandle = $value['entryTypeHandle'];

        return $this->owner;
    }

    public function endsBeforeDate(
        string|array $value,
        string|DateTimeInterface|int $date = null,
        string|bool $entryTypeHandle = null
    ): Component|null {
        $value = $this->parseDateArgumentValue($value, $date, $entryTypeHandle);

        $this->handle = $value['handle'];
        $this->endsBeforeDate = $value['date'];
        $this->entryTypeHandle = $value['entryTypeHandle'];

        return $this->owner;
    }

    public function isDuringDate(
        string|array $value,
        string|DateTimeInterface|int $date = null,
        string|bool $entryTypeHandle = null
    ): Component|null {
        $value = $this->parseDateRangeArgumentValue($value, $date, $entryTypeHandle);

        $this->handle = $value['handle'];
        $this->isDuringDate = $value['dateRange'];
        $this->entryTypeHandle = $value['entryTypeHandle'];

        return $this->owner;
    }

    public function isNotDuringDate(
        string|array $value,
        string|DateTimeInterface|int $date = null,
        string|bool $entryTypeHandle = null
    ): Component|null {
        $value = $this->parseDateRangeArgumentValue($value, $date, $entryTypeHandle);

        $this->handle = $value['handle'];
        $this->isNotDuringDate = $value['dateRange'];
        $this->entryTypeHandle = $value['entryTypeHandle'];

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

            if (
                $field && $this->startsAfterDate
                && ($date = DateTimeHelper::toDateTime($this->startsAfterDate))
            ) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('start'),
                        $date->format('Y-m-d'),
                        '>'
                    ));
            }

            if (
                $field && $this->endsBeforeDate
                && ($date = DateTimeHelper::toDateTime($this->endsBeforeDate))
            ) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('end'),
                        $date->format('Y-m-d'),
                        '<'
                    ));
            }

            if (
                $field && $this->isDuringDate
                && ($dateRange = DateRange::toDateRange($this->isDuringDate))
            ) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('start'),
                        $dateRange['end']->format('Y-m-d'),
                        '<='
                    ))
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('end'),
                        $dateRange['start']->format('Y-m-d'),
                        '>='
                    ));
            }

            if (
                $field && $this->isNotDuringDate
                && ($dateRange = DateRange::toDateRange($this->isNotDuringDate))
            ) {
                $this->owner->subQuery
                    ->andWhere([
                        'or',
                        Db::parseDateParam(
                            $field->getValueSql('start'),
                            $dateRange['end']->format('Y-m-d'),
                            '>'
                        ),
                        Db::parseDateParam(
                            $field->getValueSql('end'),
                            $dateRange['start']->format('Y-m-d'),
                            '<'
                        )
                    ]);
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

            if (
                $field && $this->startsAfterDate
                && ($date = DateTimeHelper::toDateTime($this->startsAfterDate))
            ) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('start'),
                        $date->format('Y-m-d'),
                        '>'
                    ));
            }

            if (
                $field && ($this->endsBeforeDate
                    && $date = DateTimeHelper::toDateTime($this->endsBeforeDate))
            ) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('end'),
                        $date->format('Y-m-d'),
                        '<'
                    ));
            }

            if (
                $field && $this->isDuringDate
                && ($dateRange = DateRange::toDateRange($this->isDuringDate))
            ) {
                $this->owner->subQuery
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('start'),
                        $dateRange['end']->format('Y-m-d'),
                        '<='
                    ))
                    ->andWhere(Db::parseDateParam(
                        $field->getValueSql('end'),
                        $dateRange['start']->format('Y-m-d'),
                        '>='
                    ));
            }

            if (
                $field && $this->isNotDuringDate
                && ($dateRange = DateRange::toDateRange($this->isNotDuringDate))
            ) {
                $this->owner->subQuery
                    ->andWhere([
                        'or',
                        Db::parseDateParam(
                            $field->getValueSql('start'),
                            $dateRange['end']->format('Y-m-d'),
                            '>'
                        ),
                        Db::parseDateParam(
                            $field->getValueSql('end'),
                            $dateRange['start']->format('Y-m-d'),
                            '<'
                        ),
                    ]);
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

    protected function parseDateArgumentValue(
        string|array|DateTimeInterface|int $value,
        string $handle = null,
        string $entryTypeHandle = null
    ): array {
        $date = null;

        if (is_array($value)) {
            $date = DateTimeHelper::toDateTime($value[0] ?? null);
            $handle = $value[1] ?? null;
            $entryTypeHandle = $value[2] ?? null;
        } else {
            $date = DateTimeHelper::toDateTime($value);
        }

        return [
            'date' => $date,
            'handle' => $handle,
            'entryTypeHandle' => $entryTypeHandle,
        ];
    }

    protected function parseDateRangeArgumentValue(
        string|array|DateTimeInterface|int $value,
        string $handle = null,
        string $entryTypeHandle = null
    ): array {
        $dateRange = null;

        if (is_array($value) && ArrayHelper::isIndexed($value)) {
            $dateRange = DateRange::toDateRange($value[0] ?? null);
            $handle = $value[1] ?? null;
            $entryTypeHandle = $value[2] ?? null;
        } else {
            $dateRange = DateRange::toDateRange($value);
        }

        return [
            'dateRange' => $dateRange,
            'handle' => $handle,
            'entryTypeHandle' => $entryTypeHandle,
        ];
    }
}
