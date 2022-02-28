<?php

namespace studioespresso\daterange\gql\arguments;

use craft\gql\arguments\elements\Entry;
use craft\gql\types\QueryArgument;
use GraphQL\Type\Definition\Type;

/**
 * Add isFuture / isPast / isOngoing to the entry queries
 *
 * Class EntriesArguments
 * @package studioespresso\daterange\gql\arguments
 */
class EntriesArguments extends Entry
{
    /**
     * Create custom arguments to add to element query
     *
     * @return array
     */
    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'isFuture' => [
                'name' => 'isFuture',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Query entries in the future'
            ],
            'isOngoing' => [
                'name' => 'isOngoing',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Query ongoing entries'
            ],
            'isPast' => [
                'name' => 'isPast',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Query entries in the past'
            ],
            'isNotPast' => [
                'name' => 'isNotPast',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Query entries where the end date is in the future'
            ]

        ]);
    }
}
