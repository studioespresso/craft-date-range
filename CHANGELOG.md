# Date Range Field for Craft CMS 3 changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## 2.5.0 - 2022-02-28
### Added
- Added ``isNotPast`` option ([#31](https://github.com/studioespresso/craft-date-range/issues/31))


## 2.4.0 - 2021-11-21
### Added
- Added GraphQL query arguments for isFuture, isCurrent and isPast. Thanks [MrHesus](https://github.com/MrHesus)! ([#25](https://github.com/studioespresso/craft-date-range/issues/25) & [#28](https://github.com/studioespresso/craft-date-range/pull/28))

## 2.3.2 - 2021-11-03
### Fixed
- Use field handle instead of name in EntryQueryBehavior ([#26](https://github.com/studioespresso/craft-date-range/issues/26))

## 2.3.1 - 2021-11-01
### Fixed
- Plugin now works with columnSuffix introduces in Craft CMS 3.7.0 ([#26](https://github.com/studioespresso/craft-date-range/issues/26))

## 2.3.0 - 2021-06-21
### Added
- PostgreSQL support

## 2.2.1 - 2021-06-08
### Added
- Elements can now be sorted by date rang fields

## 2.2.0 - 2021-03-21
### Added
- Add a field option and validtor to force the end date later than start date ([#23](https://github.com/studioespresso/craft-date-range/issues/23))

### Fixed
- Fixed an issue with ``formatted()`` when the field didn't use time fields ([#22](https://github.com/studioespresso/craft-date-range/issues/22))


## 2.1.2 - 2020-11-03
### Fixed 
- Fixed an issue with ``formatted()`` where the start date was used instead of the end date.


## 2.1.1 - 2020-10-19
### Added
- ``formatted()`` now outputs dates in the current site locale


## 2.1.0 - 2020-02-27
### Added
- Added more options to the ``formatted()`` option and documentation on how to use them ([#15](https://github.com/studioespresso/craft-date-range/issues/15))

## 2.0.2 - 2020-02-24
### Fixed
- Fixed an issue where the field wasn't working from the quick post widget ([#14](https://github.com/studioespresso/craft-date-range/issues/14))

## 2.0.1 - 2020-01-06
### Added
- When the field is added to element overview, the values are now formatted based on the current user's locale ([#10](https://github.com/studioespresso/craft-date-range/issues/10))

## 2.0.0 - 2019-12-12
### Added
- Full support for Craft's GraphQL API. ([#8](https://github.com/studioespresso/craft-date-range/issues/8))
### Fixed
- `isFuture` on the field value is now based on the start date instead of the end date.

## 1.2.2 - 2019-12-11
### Fixed
- Field labels are now translatable
- When saving a field with only a start date, we'll now use that as value for the end date as well. [#4](https://github.com/studioespresso/craft-date-range/issues/4)

### Added
- Added french translation of the field labels (Thanks [@ockam](https://github.com/ockam), [#6](https://github.com/studioespresso/craft-date-range/issues/6))
- Added dutch translations of the field labels 
- Added optional `includeToday` parameter to entry query behaviour

## 1.2.1 - 2019-12-02
### Added
- ``isFuture()`` and ``getIsFuture()`` now use the end date to determine when an event is over

## 1.2.0 - 2019-11-30
### Added
- ``isPast()``, ``isOnGoing()`` and ``isFuture()`` can now be used in entry queries, passing along the field handle you want to be used to the function. (requires MySQL 5.7 or higher)


## 1.1.0 - 2019-11-29
### Added
- The field can now be displayed on element overview pages in the CP
- Added the `getFormatted` option to the field to display all data in 1 line 

## 1.0.1 - 2019-11-21
### Fixed 
- Fixed an issue where using `isOngoing()` wouldn't use the correct data and would crash. [#2](https://github.com/studioespresso/craft-date-range/issues/2)
- Better date format parsing using ``DateTimeHelper::toDateTime()``

## 1.0.0 - 2019-10-24
### Added
- Initial release 🎉
