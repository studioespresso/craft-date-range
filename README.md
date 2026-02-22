# Date Range field for Craft CMS

What is says on the tin 🙂. This field gives you a start and end date in 1 field.

<img src="https://www.studioespresso.co/assets/date-range-github-banner.png">

## Requirements

This plugin requires Craft CMS 3, 4 or 5.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project
        composer require studioespresso/craft-date-range
        ./craft install/plugin date-range

## Settings
The following options can be set on the field:
- Show a start time field
- Show an end time field
- End date should be after or later thand the start date

When the field is set to required, both start & end date (and if enabled time) will be required. 

## Default time values
Since a PHP ``DateTime`` object also has a time value, wether you entered on or not (or wether you have to option enabled to show the fields or not), the plugin tries to be smart  in which time values get saved.

When you enable either or both time fields, that value will off course be safed. For fields that don't have time options set, ``00:00:00`` will get saved.

## Upgrading to Craft 5
With Craft 5 comes multi-instance support (fields can be used multiple times in the same layout) and this adds a bit of complexity for the Date Range plugin.
In the query behaviour, you'll need to add the handle of the entry type you're trying to query as a second argument.

````twig
// Craft 4
{% set events = craft.entries.section('events').isFuture('dateRangeFieldHandle')  %}

// Craft 5
{% set events = craft.entries.section('events').isFuture('dateRangeFieldHandle', 'entryTypeHandle')  %}
````

## Templating

### Element queries
⚠️ Using date range fields in your entry queries is possible but requires the site to be running **MySQL 5.7 or later** or **PostgreSQL 9.3 or later**.

Example:

```twig
{% set events = craft.entries.section('events').isFuture('dateRangeFieldHandle', 'entryTypeHandle')  %}
```

The plugin includes the following query behaviors:
- `isFuture()` - entries where the start date is in the future
- `isPast()` - entries where the end date is in the past
- `isNotPast()` - entries where the end date is in the future
- `isOnGoing()` - entries where the start date is in the past and the end date is in the future
- `startsAfterDate()` - entries where the start date is after the given date
- `endsBeforeDate()` - entries where the end date is before the given date
- `isDuringDate()` - entries where the date range overlaps with the given date or date range
- `isNotDuringDate()` - entries where the date range does not overlap with the given date or date range

The second argument passed should be the handle of the entry type you want to query.

You can optionally pass `true` as a third argument to `isFuture`, `isPast`, `isNotPast` and `isOnGoing` to include events that happen today.

The `startsAfterDate`, `endsBeforeDate`, `isDuringDate` and `isNotDuringDate` methods accept a date as the first argument, followed by the field handle and entry type handle:

```twig
{# Entries starting after a specific date #}
{% set events = craft.entries.section('events').startsAfterDate('2025-06-01', 'dateRangeFieldHandle', 'entryTypeHandle').all() %}

{# Entries ending before a specific date #}
{% set events = craft.entries.section('events').endsBeforeDate('2025-12-31', 'dateRangeFieldHandle', 'entryTypeHandle').all() %}

{# Entries overlapping with a single date #}
{% set events = craft.entries.section('events').isDuringDate('2025-06-15', 'dateRangeFieldHandle', 'entryTypeHandle').all() %}

{# Entries overlapping with a date range #}
{% set events = craft.entries.section('events').isDuringDate('2025-06-01 => 2025-06-30', 'dateRangeFieldHandle', 'entryTypeHandle').all() %}

{# Entries NOT overlapping with a date range #}
{% set events = craft.entries.section('events').isNotDuringDate('2025-06-01 => 2025-06-30', 'dateRangeFieldHandle', 'entryTypeHandle').all() %}
```

### Field values
When using the field in your template, you have access to both `start` and `end` properties, as well as:
- `getFormatted()`: which optionally accepts a date(time) format (eg: 'd/m/Y') as the first parameter and a seperator string as the second (eg: ' until ').
- `isPast`: returns `true` if the `end` property is past the current date & time.
- `isFuture`: returns `true` if the `start` property is ahead the current date & time.
- `isOnGoing`: returns `true` if the `start` property is past the current date & time *and* the `end` property is ahead of the current date & time.
- `isNotPast`: returns `true` if the `end` property is ahead of the current date & time.
- `startsAfterDate(date)`: returns `true` if the `start` property is after the given date.
- `endsBeforeDate(date)`: returns `true` if the `end` property is before the given date.
- `isDuringDate(date)`: returns `true` if the date range overlaps with the given date or date range.
- `isNotDuringDate(date)`: returns `true` if the date range does not overlap with the given date or date range.

The `isDuringDate` and `isNotDuringDate` methods accept a single date string, a date range string (using `=>` as separator), or an array with `start` and `end` keys:

```twig
{% if entry.dateRangeField.isDuringDate('2025-06-15') %}...{% endif %}
{% if entry.dateRangeField.isDuringDate('2025-06-01 => 2025-06-30') %}...{% endif %}
{% if entry.dateRangeField.isNotDuringDate('2025-07-01 => 2025-07-31') %}...{% endif %}
```

### `getFormatted()`
When using the ``getFormatted()`` function, you can pass paramters in 2 ways:
1) a date format and a separator string (eg: ``entry.dateRangeHandle.formatted("d/m/Y Hi", "until"|t)``)  
2) an array with a ``date`` and a ``time`` key and a separator string (eg: ``entry.dateRangeHandle.formatted({ date: 'd/m/Y', time: 'H:i:s'}, 'tot'|t)``)

With this second option, the field can output date and time seperatly and when the start and end dates are the same, it will only ouput one, using the separate time formates for the start and end times (eg `` 30/04/2020 11:00 until 16:00`` )

## GraphQL
The field has full support for Craft's GraphQL api, which was added in Craft CMS 3.3
You have access to the same properties as you do in Twig, and you can also use Craft's ``@formatDateTime`` to change the date formats.  

```graphql
query{
  entries(
   section: "events",
   isFuture: ["dateRangeFieldHandle", true]
  ) {
    title
    ... on events_events_Entry {
      dateRangeFieldHandle {
        start
        end @formatDateTime(format: "d M Y")
        isPast
        isOnGoing
        isFuture
      }
    }
  }
}
```

The following GraphQL query arguments are also available: `startsAfterDate`, `endsBeforeDate`, `isDuringDate`, and `isNotDuringDate`.

```graphql
query{
  entries(
   section: "events",
   isDuringDate: ["2025-06-01 => 2025-06-30", "dateRangeFieldHandle", "entryTypeHandle"]
  ) {
    title
  }
}
```
----

Brought to you by [Studio Espresso](https://www.studioespresso.co)
