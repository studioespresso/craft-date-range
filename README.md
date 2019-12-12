# Date Range field for Craft CMS 3

What is says on the tin 🙂. This field gives you a start and end date in 1 field.

## Requirements

This plugin requires Craft CMS 3.0.0 or later.

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

When the field is set to required, both start & end date (and if enabled time) will be required. 

## Default time values
Since a PHP ``DateTime`` object also has a time value, wether you entered on or not (or wether you have to option enabled to show the fields or not), the plugin tries to be smart  in which time values get saved.

When you enable either or both time fields, that value will off course be safed. For fields that don't have time options set, ``00:00:00`` will get saved.


## Templating

### Element queries
⚠️ Using date range fields in your entry queries is possible but requires the site to be running **MySQL 5.7 or highter**.

Example:

```twig
{% set events = craft.entries.section('events').isFuture('dateRangeFieldHandle')  %}
```

The plugin includes `isOnGoing()`, `isPast()` and `isFuture()` query behaviors. You can optionally pass `true` as a second argument to the query to make it include events that happen today in future/past/onGoing queries. 

### Field values
When using the field in your template, you have access to both `start` and `end` properties, as well as:
- `getFormatted()`: which optionally accepts a date(time) format (eg: 'd/m/Y') as the first parameter and a seperator string as the second (eg: ' until ').
- `isPast`: returns `true` if the `end` property is past the current date & time.
- `isFuture`: returns `true` if the `start` property is ahead the current date & time.
- `isOnGoing`: returns `true` if the `start` property is past the current date & time *and* the `end` property is ahead of the current date & time.

## GraphQL
The field has full support for Craft's GraphQL api, which was added in Craft CMS 3.3
You have access to the same properties as you do in Twig, and you can also use Craft's ``@formatDateTime`` to change the date formats.  

```graphql
query{
  entries(section: "events") {
    title
    ... on events_events_Entry {
      dateRange {
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
----

Brought to you by [Studio Espresso](https://studioespresso.co/en)
