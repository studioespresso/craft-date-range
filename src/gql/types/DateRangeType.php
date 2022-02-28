<?php


namespace studioespresso\daterange\gql\types;

use craft\gql\base\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use studioespresso\daterange\fields\data\DateRangeData;

/**
 * @author    Studio Espresso
 * @package   DateRange
 * @since     1.3.0
 */
class DateRangeType extends ObjectType
{
    /**
     * @inheritdoc
     */
    protected function resolve($source, $arguments, $context, ResolveInfo $resolveInfo)
    {
        /** @var DateRangeData $source */
        $fieldName = $resolveInfo->fieldName;

        switch ($fieldName) {
            case 'start':
                return $source->start;
                break;

            case 'end':
                return $source->end;
                break;

            case 'isPast':
                return $source->isPast;
                break;

            case 'isNotPast':
                return $source->isNotPast;
                break;

            case 'isFuture':
                return $source->isFuture;
                break;

            case 'isOnGoing':
                return $source->isOnGoing;
                break;
            default:
                return $source->$fieldName;
                break;


        }
    }
}
