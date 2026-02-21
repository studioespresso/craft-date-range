<?php

namespace studioespresso\daterange\tests\unit;

use Codeception\Test\Unit;
use Craft;
use craft\elements\Entry;
use craft\elements\User;
use craft\fieldlayoutelements\CustomField;
use craft\helpers\StringHelper;
use craft\models\EntryType;
use craft\models\FieldLayout;
use craft\models\FieldLayoutTab;
use craft\models\Section;
use craft\models\Section_SiteSettings;
use studioespresso\daterange\fields\DateRangeField;

class EntryQueryIncludeTodayTest extends Unit
{
    private int $startsTodayId;
    private int $endsTodayId;
    private Section $section;

    protected function _before()
    {
        parent::_before();

        // Use the Craft system timezone so stored UTC values match
        // the query comparison (behavior uses assumeSystemTimeZone=true)
        $tz = Craft::$app->getTimeZone();
        $today = date('Y-m-d');

        // Create DateRangeField
        $field = new DateRangeField([
            'name' => 'Event Dates',
            'handle' => 'eventDates',
        ]);
        Craft::$app->getFields()->saveField($field);

        // Create EntryType
        $type = new EntryType([
            'name' => 'Event',
            'handle' => 'event',
            'hasTitleField' => true,
            'titleFormat' => null,
            'uid' => StringHelper::UUID(),
        ]);
        Craft::$app->getEntries()->saveEntryType($type);
        $entryType = Craft::$app->getEntries()->getEntryTypeByHandle('event');

        // Create FieldLayout with the DateRangeField
        $fieldLayout = new FieldLayout(['type' => Entry::class]);
        $tab = new FieldLayoutTab(['name' => 'Content', 'sortOrder' => 1]);
        $fieldLayout->setTabs([$tab]);
        $tab->setElements([new CustomField($field)]);
        Craft::$app->getFields()->saveLayout($fieldLayout);
        $entryType->fieldLayoutId = $fieldLayout->id;
        Craft::$app->getEntries()->saveEntryType($entryType);

        // Create Section
        $this->section = new Section([
            'name' => 'Events',
            'handle' => 'events',
            'type' => Section::TYPE_CHANNEL,
            'siteSettings' => [
                new Section_SiteSettings([
                    'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
                    'enabledByDefault' => true,
                    'hasUrls' => false,
                    'uriFormat' => null,
                    'template' => null,
                ]),
            ],
            'entryTypes' => [$entryType],
        ]);
        Craft::$app->getEntries()->saveSection($this->section);

        // Find or create an author
        $author = User::find()->one();
        if (!$author) {
            $author = new User();
            $author->username = 'testuser';
            $author->email = 'test@example.com';
            Craft::$app->getElements()->saveElement($author);
        }

        // Boundary entries: start or end set to today (midnight in system timezone)
        $this->startsTodayId = $this->saveEntry($entryType, $author->id, 'Starts Today', $today, '2030-06-01', $tz);
        $this->endsTodayId = $this->saveEntry($entryType, $author->id, 'Ends Today', '2020-01-01', $today, $tz);

        Craft::$app->getDb()->getTransaction()?->commit();
        Craft::$app->getElements()->invalidateAllCaches();
        Craft::$app->getCache()->flush();
    }

    private function saveEntry(EntryType $entryType, int $authorId, string $title, string $start, string $end, string $tz): int
    {
        $entry = new Entry();
        $entry->sectionId = $this->section->id;
        $entry->typeId = $entryType->id;
        $entry->authorId = $authorId;
        $entry->title = $title;
        $entry->slug = StringHelper::toKebabCase($title);
        $entry->enabled = true;

        // Pass dates with explicit timezone so the stored UTC value
        // matches what the behavior's parseDateParam produces
        $entry->setFieldValue('eventDates', [
            'start' => ['date' => $start, 'timezone' => $tz],
            'end' => ['date' => $end, 'timezone' => $tz],
        ]);

        if (!Craft::$app->getElements()->saveElement($entry, false)) {
            throw new \Exception('Failed to save entry "' . $title . '": ' . implode(', ', $entry->getFirstErrors()));
        }

        return $entry->id;
    }

    private function queryIds(string $method, bool $includeToday = false): array
    {
        return array_map('intval', Entry::find()
            ->section('events')
            ->$method('eventDates', 'event', $includeToday)
            ->ids());
    }

    protected function _after()
    {
        parent::_after();

        $section = Craft::$app->getEntries()->getSectionByHandle('events');
        if ($section) {
            Craft::$app->getEntries()->deleteSection($section);
        }

        $type = Craft::$app->getEntries()->getEntryTypeByHandle('event');
        if ($type) {
            Craft::$app->getEntries()->deleteEntryType($type);
        }

        $field = Craft::$app->getFields()->getFieldByHandle('eventDates');
        if ($field) {
            Craft::$app->getFields()->deleteField($field);
        }
    }

    // isFuture: start > today vs start >= today

    public function testIsFutureExcludesStartsTodayWithoutIncludeToday()
    {
        $ids = $this->queryIds('isFuture', false);

        $this->assertNotContains($this->startsTodayId, $ids);
    }

    public function testIsFutureIncludesStartsTodayWithIncludeToday()
    {
        $ids = $this->queryIds('isFuture', true);

        $this->assertContains($this->startsTodayId, $ids);
    }

    // isPast: end < today vs end <= today

    public function testIsPastExcludesEndsTodayWithoutIncludeToday()
    {
        $ids = $this->queryIds('isPast', false);

        $this->assertNotContains($this->endsTodayId, $ids);
    }

    public function testIsPastIncludesEndsTodayWithIncludeToday()
    {
        $ids = $this->queryIds('isPast', true);

        $this->assertContains($this->endsTodayId, $ids);
    }

    // isNotPast: end > today vs end >= today

    public function testIsNotPastExcludesEndsTodayWithoutIncludeToday()
    {
        $ids = $this->queryIds('isNotPast', false);

        $this->assertNotContains($this->endsTodayId, $ids);
    }

    public function testIsNotPastIncludesEndsTodayWithIncludeToday()
    {
        $ids = $this->queryIds('isNotPast', true);

        $this->assertContains($this->endsTodayId, $ids);
    }

    // isOnGoing: start < today AND end > today vs start <= today AND end >= today

    public function testIsOnGoingExcludesBoundaryEntriesWithoutIncludeToday()
    {
        $ids = $this->queryIds('isOnGoing', false);

        $this->assertNotContains($this->startsTodayId, $ids);
        $this->assertNotContains($this->endsTodayId, $ids);
    }

    public function testIsOnGoingIncludesBoundaryEntriesWithIncludeToday()
    {
        $ids = $this->queryIds('isOnGoing', true);

        $this->assertContains($this->startsTodayId, $ids);
        $this->assertContains($this->endsTodayId, $ids);
    }
}
