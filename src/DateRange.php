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

use studioespresso\daterange\fields\DateRangeField;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use craft\events\PluginEvent;
use craft\services\Fields;
use craft\events\RegisterComponentTypesEvent;

use yii\base\Event;

/**
 * Class DateRange
 *
 * @author    Studio Espresso
 * @package   DateRange
 * @since     1.0.0
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
    }
}
