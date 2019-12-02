# Date Range Field for Craft CMS 3 changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).


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
