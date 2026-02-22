<?php
/**
 * Date Range plugin for Craft CMS 3.x
 *
 * Date range field
 *
 * @link      https://studioespresso.co/en
 * @copyright Copyright (c) 2019 Studio Espresso
 */

namespace studioespresso\daterange;

use Craft;
use craft\base\Plugin;
use craft\elements\db\EntryQuery;
use craft\events\DefineBehaviorsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterGqlQueriesEvent;
use craft\helpers\DateTimeHelper;
use craft\services\Fields;
use craft\services\Gql;
use DateTimeInterface;
use studioespresso\daterange\behaviors\EntryQueryBehavior;
use studioespresso\daterange\fields\DateRangeField;
use studioespresso\daterange\gql\arguments\EntriesArguments;
use yii\base\Event;

/**
 * Class DateRange
 *
 * @author    Studio Espresso
 * @package   DateRange
 * @since     1.0.0
 *
 *
 */
class DateRange extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * @var DateRange
     */
    public static $plugin;

    // Static Methods
    // =========================================================================

    public static function toDateRange( string|array|DateTimeInterface|int $value ): array|null
    {
        $start = $value;
        $end = $value;

        if (is_string($value)) {
            $value = preg_split('/\s?=>\s?/', $start);
            $start = $value[0] ?? null;
            $end = $value[1] ?? $start;
        } else if (is_array($value)) {
            $start = $value['start'] ?? null;
            $end = $value['end'] ?? $start;
        }

        $start = DateTimeHelper::toDateTime($start);
        $end = DateTimeHelper::toDateTime($end);

        if (!$start || !$end || $end->getTimestamp() < $start->getTimestamp()) {
            return null;
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public string $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = DateRangeField::class;
            }
        );

        if (
            Craft::$app->db->getIsMysql() ||
            (Craft::$app->db->getIsPgsql() && version_compare(Craft::$app->db->getServerVersion(), "9.3", ">="))
        ) {
            Event::on(EntryQuery::class, EntryQuery::EVENT_DEFINE_BEHAVIORS, function(DefineBehaviorsEvent $event) {
                $event->behaviors[$this->id] = EntryQueryBehavior::class;
            });
        }

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_QUERIES,
            function(RegisterGqlQueriesEvent $event) {
                // Add date-range query arguments `isFuture`, `isOngoing`, `isPast`, `isNotPast`, `isOngoing`,
                // `startsAfterDate`, `endsBeforeDate`, `isDuringDate`, `isNotDuringDate`
                $arguments = EntriesArguments::getArguments();

                // Only update the args key
                $event->queries['entries']['args'] = $arguments;
                $event->queries['entryCount']['args'] = $arguments;
                $event->queries['entry']['args'] = $arguments;
            }
        );
    }
}
