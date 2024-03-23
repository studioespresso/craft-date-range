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

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\PreviewableFieldInterface;
use craft\base\SortableFieldInterface;
use craft\helpers\Db;
use craft\i18n\Locale;
use GraphQL\Type\Definition\Type;
use studioespresso\daterange\fields\data\DateRangeData;
use studioespresso\daterange\gql\types\generators\DateRangeGenerator;
use studioespresso\daterange\validators\EndDateValidator;
use yii\db\Schema;

/**
 * @author    Studio Espresso
 * @package   DateRange
 * @since     1.0.0
 */
class DateRangeField extends Field implements PreviewableFieldInterface, SortableFieldInterface
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $someAttribute = 'Some Default';

    public $showStartTime = false;

    public $showEndTime = false;

    public $endAfterStart = true;

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
    public function rules(): array
    {
        $rules = parent::rules();
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public static function dbType(): array|string|null
    {
        return [
            'start' => Schema::TYPE_DATETIME,
            'end' => Schema::TYPE_DATETIME,
        ];
    }

    public static function isMultiInstance(): bool
    {
        return true;
    }

    public function getPreviewHtml(mixed $value, ElementInterface $element): string
    {
        $formatter = Craft::$app->getFormatter();
        if (!$value) {
            return false;
        }
        if ($value->start->format('dmyhis') === $value->end->format('dmyhis')) {
            if ($this->getSettings()['showStartTime']) {
                return $formatter->asDatetime($value->start, Locale::LENGTH_SHORT);
            } else {
                return $formatter->asDate($value->start, Locale::LENGTH_SHORT);
            }
        } else {
            if ($this->getSettings()['showStartTime'] && $this->getSettings()['showEndTime']) {
                return $formatter->asDatetime($value->start, Locale::LENGTH_SHORT) . ' - ' .
                    $formatter->asDatetime($value->end, Locale::LENGTH_SHORT);
            } elseif ($this->getSettings()['showStartTime'] && !$this->getSettings()['showEndTime']) {
                return $formatter->asDatetime($value->start, Locale::LENGTH_SHORT) . ' - ' .
                    $formatter->asDate($value->end, Locale::LENGTH_SHORT);
            } elseif (!$this->getSettings()['showStartTime'] && $this->getSettings()['showEndTime']) {
                return $formatter->asDate($value->start, Locale::LENGTH_SHORT) . ' - ' .
                    $formatter->asDatetime($value->end, Locale::LENGTH_SHORT);
            } else {
                return $formatter->asDate($value->start, Locale::LENGTH_SHORT) . ' - ' .
                    $formatter->asDate($value->end, Locale::LENGTH_SHORT);
            }
        }
    }
    
    public function getContentGqlType(): Type|array
    {
        $typeArray = DateRangeGenerator::generateTypes($this);

        return [
            'name' => $this->handle,
            'description' => 'Date Range field',
            'type' => array_shift($typeArray),
        ];
    }

    public function getElementValidationRules(): array
    {
        if ($this->endAfterStart) {
            return [EndDateValidator::class];
        }
        return [];
    }


    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null): mixed
    {
        if (!$value) {
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
    public function serializeValue($value, ElementInterface $element = null): mixed
    {
        if (!$value) {
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
    public function getSettingsHtml(): ?string
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
