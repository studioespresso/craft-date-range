<?php

namespace studioespresso\daterange\tests\unit;

use Codeception\Test\Unit;
use Craft;
use craft\db\Query;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use studioespresso\daterange\behaviors\EntryQueryBehavior;
use studioespresso\daterange\fields\DateRangeField;
use yii\base\InvalidConfigException;

class EntryQueryBehaviorTest extends Unit
{
    private EntryQuery $query;
    private DateRangeField $dateRangeField;

    protected function _before()
    {
        parent::_before();
        $this->query = Entry::find();

        // Create a DateRangeField
        $this->dateRangeField = new DateRangeField([
            'name' => 'Event Dates',
            'handle' => 'eventDates',
        ]);
        Craft::$app->getFields()->saveField($this->dateRangeField);

        // Create an EntryType
        $type = new EntryType([
            'name' => 'Event',
            'handle' => 'event',
            'hasTitleField' => true,
            'titleFormat' => null,
            'uid' => StringHelper::UUID(),
        ]);
        Craft::$app->getEntries()->saveEntryType($type);
        $entryType = Craft::$app->getEntries()->getEntryTypeByHandle('event');

        // Create a FieldLayout with the field attached
        $fieldLayout = new FieldLayout([
            'type' => Entry::class,
        ]);
        $tab = new FieldLayoutTab([
            'name' => 'Content',
            'sortOrder' => 1,
        ]);
        // Attach tab to layout first so it has the back-reference
        $fieldLayout->setTabs([$tab]);
        $tab->setElements([new CustomField($this->dateRangeField)]);
        Craft::$app->getFields()->saveLayout($fieldLayout);

        // Assign layout to entry type
        $entryType->fieldLayoutId = $fieldLayout->id;
        Craft::$app->getEntries()->saveEntryType($entryType);
    }

    protected function _after()
    {
        parent::_after();

        $type = Craft::$app->getEntries()->getEntryTypeByHandle('event');
        if ($type) {
            Craft::$app->getEntries()->deleteEntryType($type);
        }

        $field = Craft::$app->getFields()->getFieldByHandle('eventDates');
        if ($field) {
            Craft::$app->getFields()->deleteField($field);
        }
    }

    private function getBehavior(): EntryQueryBehavior
    {
        return $this->query->getBehavior('date-range');
    }

    private function getSubQueryWhereString(): string
    {
        $where = $this->query->subQuery->where;
        if ($where === null) {
            return '';
        }
        return print_r($where, true);
    }

    // ---- Existing property-setting tests ----

    public function testBehaviorIsAttached()
    {
        $this->assertInstanceOf(EntryQueryBehavior::class, $this->getBehavior());
    }

    public function testIsFutureSetsPropertiesAndReturnsOwner()
    {
        $result = $this->query->isFuture('myField', 'myType');

        $this->assertSame($this->query, $result);
        $this->assertEquals('myField', $this->getBehavior()->handle);
        $this->assertTrue($this->getBehavior()->isFuture);
        $this->assertEquals('myType', $this->getBehavior()->entryTypeHandle);
        $this->assertFalse($this->getBehavior()->includeToday);
    }

    public function testIsPastSetsPropertiesAndReturnsOwner()
    {
        $result = $this->query->isPast('myField', 'myType');

        $this->assertSame($this->query, $result);
        $this->assertEquals('myField', $this->getBehavior()->handle);
        $this->assertTrue($this->getBehavior()->isPast);
        $this->assertEquals('myType', $this->getBehavior()->entryTypeHandle);
    }

    public function testIsNotPastSetsPropertiesAndReturnsOwner()
    {
        $result = $this->query->isNotPast('myField', 'myType');

        $this->assertSame($this->query, $result);
        $this->assertEquals('myField', $this->getBehavior()->handle);
        $this->assertTrue($this->getBehavior()->isNotPast);
        $this->assertEquals('myType', $this->getBehavior()->entryTypeHandle);
    }

    public function testIsOnGoingSetsPropertiesAndReturnsOwner()
    {
        $result = $this->query->isOnGoing('myField', 'myType');

        $this->assertSame($this->query, $result);
        $this->assertEquals('myField', $this->getBehavior()->handle);
        $this->assertTrue($this->getBehavior()->isOnGoing);
        $this->assertEquals('myType', $this->getBehavior()->entryTypeHandle);
    }

    public function testStringValueSetsHandle()
    {
        $this->query->isFuture('eventDate', 'event');

        $this->assertEquals('eventDate', $this->getBehavior()->handle);
    }

    public function testArrayWithTypeHandleParsesCorrectly()
    {
        $this->query->isFuture(['eventDate', 'event']);

        $this->assertEquals('eventDate', $this->getBehavior()->handle);
        $this->assertEquals('event', $this->getBehavior()->entryTypeHandle);
    }

    public function testArrayWithIncludeTodayParsesCorrectly()
    {
        $this->query->isFuture(['eventDate', true], 'event');

        $this->assertEquals('eventDate', $this->getBehavior()->handle);
        $this->assertTrue($this->getBehavior()->includeToday);
    }

    public function testIncludeTodayDefaultsToFalse()
    {
        $this->query->isFuture('myField', 'myType');

        $this->assertFalse($this->getBehavior()->includeToday);
    }

    // ---- onAfterPrepare: error handling ----

    public function testOnAfterPrepareThrowsWhenEntryTypeHandleMissing()
    {
        $behavior = $this->getBehavior();
        $behavior->handle = 'eventDates';
        $behavior->entryTypeHandle = null;

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('entryType not specified');
        $behavior->onAfterPrepare();
    }

    public function testOnAfterPrepareThrowsWhenEntryTypeHandleInvalid()
    {
        $behavior = $this->getBehavior();
        $behavior->handle = 'eventDates';
        $behavior->entryTypeHandle = 'nonExistentType';

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Invalid entryType specified');
        $behavior->onAfterPrepare();
    }

    public function testOnAfterPrepareDoesNothingWhenNoHandleSet()
    {
        $behavior = $this->getBehavior();
        $behavior->onAfterPrepare();

        $this->assertFalse($behavior->field);
    }

    // ---- onAfterPrepare: field retrieval + SQL modification ----

    public function testOnAfterPrepareLoadsFieldFromLayout()
    {
        $this->query->isFuture('eventDates', 'event');
        $this->query->subQuery = new Query();

        $this->getBehavior()->onAfterPrepare();

        $this->assertInstanceOf(DateRangeField::class, $this->getBehavior()->field);
    }

    public function testIsFutureAddsWhereOnStartColumn()
    {
        $this->query->isFuture('eventDates', 'event');
        $this->query->subQuery = new Query();

        $this->getBehavior()->onAfterPrepare();

        $whereStr = $this->getSubQueryWhereString();
        $this->assertNotEmpty($whereStr);
        $this->assertStringContainsString('start', $whereStr);
    }

    public function testIsPastAddsWhereOnEndColumn()
    {
        $this->query->isPast('eventDates', 'event');
        $this->query->subQuery = new Query();

        $this->getBehavior()->onAfterPrepare();

        $whereStr = $this->getSubQueryWhereString();
        $this->assertNotEmpty($whereStr);
        $this->assertStringContainsString('end', $whereStr);
    }

    public function testIsNotPastAddsWhereOnEndColumn()
    {
        $this->query->isNotPast('eventDates', 'event');
        $this->query->subQuery = new Query();

        $this->getBehavior()->onAfterPrepare();

        $whereStr = $this->getSubQueryWhereString();
        $this->assertNotEmpty($whereStr);
        $this->assertStringContainsString('end', $whereStr);
    }

    public function testIsOnGoingAddsWhereOnBothColumns()
    {
        $this->query->isOnGoing('eventDates', 'event');
        $this->query->subQuery = new Query();

        $this->getBehavior()->onAfterPrepare();

        $whereStr = $this->getSubQueryWhereString();
        $this->assertNotEmpty($whereStr);
        $this->assertStringContainsString('start', $whereStr);
        $this->assertStringContainsString('end', $whereStr);
    }

    public function testFieldNotInLayoutAddsNoWhere()
    {
        $this->query->isFuture('nonExistentField', 'event');
        $this->query->subQuery = new Query();

        $this->getBehavior()->onAfterPrepare();

        $this->assertNull($this->getBehavior()->field);
        $this->assertNull($this->query->subQuery->where);
    }
}
