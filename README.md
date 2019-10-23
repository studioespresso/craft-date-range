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

## Templating
When using the field in your template, you have access to both `start` and `end` properties, as well as:
- `isPast`: returns `true` if the `end` property is past the current date & time.
- `isFuture`: returns `true` if the `start` property is ahead the current date & time.
- `isOnGoing`: returns `true` if the `start` property is past the current date & time *and* the `end` property is ahead of the current date & time.

Brought to you by [Studio Espresso](https://studioespresso.co/en)
