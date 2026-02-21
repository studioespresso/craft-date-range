# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Craft CMS 5 plugin providing a Date Range field type with start/end date+time, temporal query methods, and GraphQL support. Developed by Studio Espresso.

- **Plugin handle**: `date-range`
- **Namespace**: `studioespresso\daterange`
- **Entry class**: `src/DateRange.php`
- **Requires**: PHP 8.2+, Craft CMS 5.0.0+

## Commands

```bash
composer check-cs    # Run ECS code style checks
composer fix-cs      # Auto-fix code style issues
composer phpstan     # Run PHPStan static analysis (level 1)
```

No test suite is configured in this plugin — testing happens in the parent Craft installation.

## Architecture

### Plugin Bootstrap (`src/DateRange.php`)

Registers three things on `init()`:
1. **Field type** — `DateRangeField` via `Fields::EVENT_REGISTER_FIELD_TYPES`
2. **Query behavior** — `EntryQueryBehavior` attached to `EntryQuery` (MySQL or PostgreSQL 9.3+ only)
3. **GraphQL arguments** — Adds `isFuture`, `isPast`, `isNotPast`, `isOngoing` to entry query arguments

### Field System

**`fields/DateRangeField.php`** — The field class. Stores two DATETIME columns (`start` and `end`) via `dbType()`. Supports Craft 5 multi-instance fields (`isMultiInstance() → true`). Configurable settings: `showStartTime`, `showEndTime`, `endAfterStart` (validation toggle).

**`fields/data/DateRangeData.php`** — Value object returned by `normalizeValue()`. Exposes `start`/`end` as DateTime objects and computed boolean properties: `isFuture`, `isPast`, `isOngoing`, `isNotPast`. The `getFormatted($format, $separator, $locale)` method handles display formatting, accepting either a string format or an array with `date`/`time` keys.

### Query Behavior (`behaviors/EntryQueryBehavior.php`)

Adds temporal filter methods to `EntryQuery`: `isFuture()`, `isPast()`, `isNotPast()`, `isOnGoing()`. In Craft 5, these require both a field handle and entry type handle due to multi-instance support. Has separate SQL generation paths for MySQL and PostgreSQL.

### GraphQL (`gql/`)

- `types/DateRangeType.php` — ObjectType exposing `start`, `end`, and temporal booleans
- `types/generators/DateRangeGenerator.php` — Generates types per field handle via `GqlEntityRegistry`
- `arguments/EntriesArguments.php` — Extends Craft's entry query arguments with date range filters

### Validation

`validators/EndDateValidator.php` — Optional Yii validator ensuring end date > start date. Enabled per-field via the `endAfterStart` setting.

### Templates

- `_components/fields/DateRange_input.twig` — Field input (start/end date pickers with conditional time inputs)
- `_components/fields/DateRange_settings.twig` — Field settings (three lightswitches)

### Translations

Supported locales: English (default), French (`fr`), Dutch (`nl`).

## Code Style

ECS uses `SetList::CRAFT_CMS_4` rules. PHPStan runs at level 1 against `src/`. CI runs both on push to `develop`/`develop-v5` and on pull requests.
