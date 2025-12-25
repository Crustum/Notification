# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1]

### Added

- Plugin manifest integration for automated configuration installation
- `manifest()` method to `NotificationPlugin` implementing `ManifestInterface`
- Automatic installation of `config/notification.php` configuration file
- Automatic installation of database migrations
- Automatic bootstrap file configuration loading
- GitHub star repository prompt support

### Changed

- Plugin now uses manifest system for configuration setup
- Configuration files are installed via `bin/cake manifest install --plugin Crustum/Notification`

## [1.0.0]

### Added

- Multi-channel notification system supporting email, database, and extensible custom channels
- NotificationManager and NotificationSender for sending notifications via NotifiableBehavior or manager
- Database notification storage with migrations, entity, and table for displaying notifications in web interface
- Queue support with ShouldQueueInterface for async notification delivery via CakePHP Queue
- Testing utilities with NotificationTrait for comprehensive test assertions and custom channel registry
