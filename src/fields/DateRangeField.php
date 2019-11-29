<?php
/**
 * Date Range plugin for Craft CMS 3.x
 *
 * Date range field
 *
 * @link      https://studioespresso.co/en
 * @copyright Copyright (c) 2019 Studio Espresso
 */

namespace studioespresso\daterange\fields;

use craft\base\PreviewableFieldInterface;
use craft\fields\data\ColorData;
use craft\validators\ColorValidator;
use studioespresso\daterange\DateRange;
use studioespresso\daterange\assetbundles\daterangefield\DateRangeFieldAsset;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Db;
use studioespresso\daterange\fields\data\DateRangeData;
use yii\db\Schema;
use craft\helpers\Json;

/**
 * @author    Studio Espresso
 * @package   DateRange
 * @since     1.0.0
 */
class DateRangeField extends Field implements PreviewableFieldInterface
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $someAttribute = 'Some Default';

    public $showStartTime = false;

    public $showEndTime = false;

    // Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('date-range', 'Date range');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING;
    }


    /**
     * @inheritdoc
     */
    public function getTableAttributeHtml($value, ElementInterface $element): string
    {
        if (!$value) {
            return false;
        }
        if ($value->start->format('dmyhis') === $value->end->format('dmyhis')) {
            if($this->getSettings()['showStartTime']) {
                return $value->start->format('d/m/Y H:i');
            } else {
                return $value->start->format('d/m/Y');

            }
        } else {
            if($this->getSettings()['showStartTime'] && $this->getSettings()['showEndTime']) {
                return $value->start->format('d/m/Y H:i') . ' - ' . $value->end->format('d/m/Y H:i');
            } elseif($this->getSettings()['showStartTime'] && !$this->getSettings()['showEndTime']) {
                return $value->start->format('d/m/Y H:i') . ' - ' . $value->end->format('d/m/Y');
            } elseif(!$this->getSettings()['showStartTime'] && $this->getSettings()['showEndTime']) {
                return $value->start->format('d/m/Y') . ' - ' . $value->end->format('d/m/Y H:i');
            } else {
                return $value->start->format('d/m/Y') . ' - ' . $value->end->format('d/m/Y');
            }
        }

    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if (!$value || $this->isFresh($element)) {
            return null;
        }

        if ($value instanceof DateRangeData) {
            return $value;
        }

        $value = DateRangeData::normalize($value, $this);
        if ($value) {
            return new DateRangeData($value);
        } else {
            return false;
        }
    }

    /**
     * @param $value DateRangeData
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        if (!$value || $this->isFresh($element)) {
            return null;
        }

        $data = [];
        if (isset($value->start)) {
            $data['start'] = Db::prepareDateForDb($value->start);
        }
        if (isset($value->end)) {
            $data['end'] = Db::prepareDateForDb($value->end);
        }
        return $data;

    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'date-range/_components/fields/DateRange_settings',
            [
                'field' => $this,
            ]
        );
    }

    public function isValueEmpty($value, ElementInterface $element): bool
    {
        return !$value ? true : false;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {

        // Get our id and namespace
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // Render the input template
        return Craft::$app->getView()->renderTemplate(
            'date-range/_components/fields/DateRange_input',
            [
                'name' => $this->handle,
                'value' => $value,
                'field' => $this,
                'id' => $id,
                'namespacedId' => $namespacedId,
            ]
        );
    }
}
