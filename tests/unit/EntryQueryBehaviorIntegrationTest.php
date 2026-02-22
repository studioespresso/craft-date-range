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

class EntryQueryBehaviorIntegrationTest extends Unit
{
    private int $pastEntryId;
    private int $futureEntryId;
    private int $ongoingEntryId;
    private Section $section;

    protected function _before()
    {
        parent::_before();

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

        // Create test entries: past, future, ongoing
        $this->pastEntryId = $this->saveEntry($entryType, $author->id, 'Past Event', '2020-01-01', '2020-06-01');
        $this->futureEntryId = $this->saveEntry($entryType, $author->id, 'Future Event', '2030-01-01', '2030-06-01');
        $this->ongoingEntryId = $this->saveEntry($entryType, $author->id, 'Ongoing Event', '2020-01-01', '2030-06-01');

        // Commit so entries are visible to queries, then clear caches
        Craft::$app->getDb()->getTransaction()?->commit();
        Craft::$app->getElements()->invalidateAllCaches();
        Craft::$app->getCache()->flush();
    }

    private function saveEntry(EntryType $entryType, int $authorId, string $title, string $start, string $end): int
    {
        $entry = new Entry();
        $entry->sectionId = $this->section->id;
        $entry->typeId = $entryType->id;
        $entry->authorId = $authorId;
        $entry->title = $title;
        $entry->slug = StringHelper::toKebabCase($title);
        $entry->enabled = true;

        $entry->setFieldValue('eventDates', [
            'start' => ['date' => $start],
            'end' => ['date' => $end],
        ]);

        if (!Craft::$app->getElements()->saveElement($entry, false)) {
            throw new \Exception('Failed to save entry "' . $title . '": ' . implode(', ', $entry->getFirstErrors()));
        }

        return $entry->id;
    }

    protected function _after()
    {
        parent::_after();

        // Clean up since we committed the transaction
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

    public function testIsFutureReturnsOnlyFutureEntries()
    {
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->isFuture('eventDates', 'event')
            ->ids());

        $this->assertContains($this->futureEntryId, $ids);
        $this->assertNotContains($this->pastEntryId, $ids);
        $this->assertNotContains($this->ongoingEntryId, $ids);
    }

    public function testIsPastReturnsOnlyPastEntries()
    {
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->isPast('eventDates', 'event')
            ->ids());

        $this->assertContains($this->pastEntryId, $ids);
        $this->assertNotContains($this->futureEntryId, $ids);
        $this->assertNotContains($this->ongoingEntryId, $ids);
    }

    public function testIsNotPastReturnsFutureAndOngoingEntries()
    {
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->isNotPast('eventDates', 'event')
            ->ids());

        $this->assertContains($this->futureEntryId, $ids);
        $this->assertContains($this->ongoingEntryId, $ids);
        $this->assertNotContains($this->pastEntryId, $ids);
    }

    public function testIsOnGoingReturnsOnlyOngoingEntries()
    {
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->isOnGoing('eventDates', 'event')
            ->ids());

        $this->assertContains($this->ongoingEntryId, $ids);
        $this->assertNotContains($this->pastEntryId, $ids);
        $this->assertNotContains($this->futureEntryId, $ids);
    }

    // ---- startsAfterDate ----

    public function testStartsAfterDateReturnsEntriesStartingAfterGivenDate()
    {
        // Past starts 2020-01-01, Future starts 2030-01-01, Ongoing starts 2020-01-01
        // Only Future starts after 2025-01-01
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->startsAfterDate('2025-01-01', 'eventDates', 'event')
            ->ids());

        $this->assertContains($this->futureEntryId, $ids);
        $this->assertNotContains($this->pastEntryId, $ids);
        $this->assertNotContains($this->ongoingEntryId, $ids);
    }

    public function testStartsAfterDateExcludesAllWhenDateIsInFuture()
    {
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->startsAfterDate('2035-01-01', 'eventDates', 'event')
            ->ids());

        $this->assertNotContains($this->pastEntryId, $ids);
        $this->assertNotContains($this->futureEntryId, $ids);
        $this->assertNotContains($this->ongoingEntryId, $ids);
    }

    // ---- endsBeforeDate ----

    public function testEndsBeforeDateReturnsEntriesEndingBeforeGivenDate()
    {
        // Past ends 2020-06-01, Future ends 2030-06-01, Ongoing ends 2030-06-01
        // Only Past ends before 2025-01-01
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->endsBeforeDate('2025-01-01', 'eventDates', 'event')
            ->ids());

        $this->assertContains($this->pastEntryId, $ids);
        $this->assertNotContains($this->futureEntryId, $ids);
        $this->assertNotContains($this->ongoingEntryId, $ids);
    }

    public function testEndsBeforeDateExcludesAllWhenDateIsInPast()
    {
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->endsBeforeDate('2015-01-01', 'eventDates', 'event')
            ->ids());

        $this->assertNotContains($this->pastEntryId, $ids);
        $this->assertNotContains($this->futureEntryId, $ids);
        $this->assertNotContains($this->ongoingEntryId, $ids);
    }

    // ---- isDuringDate ----

    public function testIsDuringDateReturnsEntriesOverlappingWithSingleDate()
    {
        // 2025-06-01 falls within Ongoing (2020-01-01 to 2030-06-01) but not Past or Future
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->isDuringDate('2025-06-01', 'eventDates', 'event')
            ->ids());

        $this->assertContains($this->ongoingEntryId, $ids);
        $this->assertNotContains($this->pastEntryId, $ids);
        $this->assertNotContains($this->futureEntryId, $ids);
    }

    public function testIsDuringDateReturnsEntriesOverlappingWithDateRange()
    {
        // 2019-06-01 => 2021-01-01 overlaps with Past (2020-01-01 to 2020-06-01) and Ongoing (2020-01-01 to 2030-06-01)
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->isDuringDate('2019-06-01 => 2021-01-01', 'eventDates', 'event')
            ->ids());

        $this->assertContains($this->pastEntryId, $ids);
        $this->assertContains($this->ongoingEntryId, $ids);
        $this->assertNotContains($this->futureEntryId, $ids);
    }

    public function testIsDuringDateReturnsAllWhenRangeSpansEverything()
    {
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->isDuringDate('2010-01-01 => 2040-01-01', 'eventDates', 'event')
            ->ids());

        $this->assertContains($this->pastEntryId, $ids);
        $this->assertContains($this->futureEntryId, $ids);
        $this->assertContains($this->ongoingEntryId, $ids);
    }

    // ---- isNotDuringDate ----

    public function testIsNotDuringDateExcludesOverlappingEntries()
    {
        // 2025-06-01 overlaps only with Ongoing — so Past and Future should be returned
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->isNotDuringDate('2025-06-01', 'eventDates', 'event')
            ->ids());

        $this->assertContains($this->pastEntryId, $ids);
        $this->assertContains($this->futureEntryId, $ids);
        $this->assertNotContains($this->ongoingEntryId, $ids);
    }

    public function testIsNotDuringDateWithRangeExcludesOverlapping()
    {
        // 2019-06-01 => 2021-01-01 overlaps Past and Ongoing — only Future should be returned
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->isNotDuringDate('2019-06-01 => 2021-01-01', 'eventDates', 'event')
            ->ids());

        $this->assertContains($this->futureEntryId, $ids);
        $this->assertNotContains($this->pastEntryId, $ids);
        $this->assertNotContains($this->ongoingEntryId, $ids);
    }

    public function testIsNotDuringDateReturnsNoneWhenAllOverlap()
    {
        // A range spanning everything should exclude all entries
        $ids = array_map('intval', Entry::find()
            ->section('events')
            ->isNotDuringDate('2010-01-01 => 2040-01-01', 'eventDates', 'event')
            ->ids());

        $this->assertNotContains($this->pastEntryId, $ids);
        $this->assertNotContains($this->futureEntryId, $ids);
        $this->assertNotContains($this->ongoingEntryId, $ids);
    }
}
