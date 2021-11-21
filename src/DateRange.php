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
use craft\services\Fields;
use craft\services\Gql;
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

    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = DateRangeField::class;
            }
        );

        if (
            Craft::$app->db->getIsMysql() ||
            (Craft::$app->db->getIsPgsql() &&  version_compare(Craft::$app->db->getServerVersion(), "9.3", ">="))
        ) {
            Event::on(EntryQuery::class, EntryQuery::EVENT_DEFINE_BEHAVIORS, function (DefineBehaviorsEvent $event) {
                $event->behaviors[$this->id] = EntryQueryBehavior::class;
            });
        }

        Event::on(
            Gql::class,
            Gql::EVENT_REGISTER_GQL_QUERIES,
            function (RegisterGqlQueriesEvent $event) {
                // Add isFuture, isOngoing, isPast to entry query arguments
                $arguments = EntriesArguments::getArguments();

                // Only update the args key
                $event->queries['entries']['args'] = $arguments;
                $event->queries['entryCount']['args'] = $arguments;
                $event->queries['entry']['args'] = $arguments;
            }
        );
    }
}
