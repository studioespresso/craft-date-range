<?php

namespace studioespresso\daterange\behaviors;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\db\EntryQuery;
use craft\helpers\Db;
use yii\base\Behavior;
use yii\base\InvalidConfigException;
use craft\commerce\Plugin as Commerce;
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

    public string|array|object|null $entryTypeHandle = null;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ElementQuery::EVENT_AFTER_PREPARE => 'onAfterPrepare',
        ];
    }

    public function isFuture($value, string|array|object|bool $entryTypeHandle = null, bool $includeToday = false)
    {
        $value = $this->parseArgumentValue($value, $entryTypeHandle, $includeToday);

        $this->handle = $value['handle'];
        $this->isFuture = true;
        $this->entryTypeHandle = $value['entryTypeHandle'];
        $this->includeToday = $value['includeToday'];

        return $this->owner;
    }

    public function isPast($value, string|array|object|bool $entryTypeHandle = null, $includeToday = false)
    {
        $value = $this->parseArgumentValue($value, $entryTypeHandle, $includeToday);

        $this->handle = $value['handle'];
        $this->isPast = true;
        $this->entryTypeHandle = $value['entryTypeHandle'];
        $this->includeToday = $value['includeToday'];
        return $this->owner;
    }

    public function isNotPast($value, string|array|object|bool $entryTypeHandle = null, $includeToday = false)
    {
        $value = $this->parseArgumentValue($value, $entryTypeHandle, $includeToday);

        $this->handle = $value['handle'];
        $this->isNotPast = true;
        $this->entryTypeHandle = $value['entryTypeHandle'];
        $this->includeToday = $value['includeToday'];
        return $this->owner;
    }

    public function isOnGoing($value, string|array|object|bool $entryTypeHandle = null, $includeToday = false)
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
            $fieldsForTypes = [];
            $entryTypes = $this->getEntryTypes();

            foreach ($entryTypes as $typeHandle => $entryType) {
                $layout = Craft::$app->getFields()->getLayoutById($entryType->fieldLayoutId);
                $field = $layout->getFieldByHandle($this->handle);
                if ($field) {
                    $fieldsForTypes[$typeHandle] = $field;
                }
            }

            // If we have fields to work with
            if (!empty($fieldsForTypes)) {
                $this->processDateQueries($fieldsForTypes);
            }
        }
    }

    /**
     * Get entry types from either handles or objects
     *
     * @return array Array of entry type objects indexed by handle
     */
    protected function getEntryTypes()
    {
        $entryTypes = [];

        // Convert to array if single value
        $types = is_array($this->entryTypeHandle) ? $this->entryTypeHandle : [$this->entryTypeHandle];

        foreach ($types as $key => $type) {
            // If it's an object with fieldLayoutId property, use it directly
            if (is_object($type) && property_exists($type, 'fieldLayoutId')) {
                $handle = property_exists($type, 'handle') ? $type->handle : 'type_' . $key;
                $entryTypes[$handle] = $type;
            }
            // If it's a string, try to get the entry type
            else if (is_string($type)) {
                $trimmedType = trim($type);
                // Try to get from Entry Types first
                $entryType = Craft::$app->getEntries()->getEntryTypeByHandle($trimmedType);
                if ($entryType) {
                    $entryTypes[$trimmedType] = $entryType;
                } else {
                    // If not found, try Commerce Product Types
                    $productType = Commerce::getInstance()->getProductTypes()->getProductTypeByHandle($trimmedType);
                    if ($productType) {
                        $entryTypes[$trimmedType] = $productType;
                    } else {
                        throw new InvalidConfigException("Invalid type specified: " . $trimmedType);
                    }
                }
            }
        }

        if (empty($entryTypes)) {
            throw new InvalidConfigException("No valid entry types were found");
        }

        return $entryTypes;
    }

    protected function processDateQueries($fieldsForTypes)
    {
        if (Craft::$app->db->getIsPgsql() || Craft::$app->db->getIsMysql()) {
            $or = ['or'];

            foreach ($fieldsForTypes as $typeHandle => $field) {
                if ($this->isFuture) {
                    $or[] = Db::parseDateParam(
                        $field->getValueSql('start'),
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    );
                }

                if ($this->isPast) {
                    $or[] = Db::parseDateParam(
                        $field->getValueSql('end'),
                        date('Y-m-d'),
                        $this->includeToday ? '<=' : '<'
                    );
                }

                if ($this->isNotPast) {
                    $or[] = Db::parseDateParam(
                        $field->getValueSql('end'),
                        date('Y-m-d'),
                        $this->includeToday ? '>=' : '>'
                    );
                }

                if ($this->isOnGoing) {
                    $and = ['and',
                        Db::parseDateParam(
                            $field->getValueSql('start'),
                            date('Y-m-d'),
                            $this->includeToday ? '<=' : '<'
                        ),
                        Db::parseDateParam(
                            $field->getValueSql('end'),
                            date('Y-m-d'),
                            $this->includeToday ? '>=' : '>'
                        )
                    ];
                    $or[] = $and;
                }
            }

            // Only add the OR condition if we have more than just the 'or' element
            if (count($or) > 1) {
                $this->owner->subQuery->andWhere($or);
            }
        }
    }

    protected function parseArgumentValue(
        string|array $value,
        string|array|object|bool $entryTypeHandle = null,
        $includeToday = false,
    ): array {
        $handle = null;

        if (is_array($value)) {
            $handle = $value[0] ?? null;
            $arg2 = $value[1] ?? null;
            if (is_string($arg2) || is_array($arg2) || is_object($arg2)) {
                $entryTypeHandle = $arg2;
            } elseif ($arg2 !== null) {
                $includeToday = $arg2;
            }
        } else {
            $handle = $value;
        }

        // If entryTypeHandle is a comma-separated string, convert it to an array
        if (is_string($entryTypeHandle) && strpos($entryTypeHandle, ',') !== false) {
            $entryTypeHandle = array_map('trim', explode(',', $entryTypeHandle));
        }

        return [
            'handle' => $handle,
            'entryTypeHandle' => $entryTypeHandle,
            'includeToday' => $includeToday,
        ];
    }
}
