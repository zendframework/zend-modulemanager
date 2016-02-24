# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.7.0 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#13](https://github.com/zendframework/zend-modulemanager/pull/13) updates the
  code base to work with the v3.0 version of zend-servicemanager. Primarily, this
  involved:
  - Removing the LocatorRegistered feature and related listener since DI was
    removed from the zend-servicemanager already.
  - Adds the setApplicationServiceManager() and getServiceManagerConfig()
    methods. The former is used to set the metadata for collecting application
    service manager configuration; the latter returns the merged configuration.
    These methods were added to both the interface and the implementation.
  - Aggregated plugins are instantiated, and added as "services" configuration
    for the service manager. When "pulled" from the service manager, the code
    uses build() to ensure the service is not cached, and to allow passing
    the configuration options.

- [#12](https://github.com/zendframework/zend-modulemanager/pull/12) updates the
  code base to work with the v3.0 version of zend-eventmanager. Primarily, this
  involved:
  - Changing trigger calls to `triggerEvent()` and ensuring the event instance
    is injected with the event name prior to trigger.
  - Ensuring aggregates are attached using the `$aggregate->attach($events)`
    signature instead of the `$events->attachAggregate($aggregate)` signature.
  - Fixing tests to inject the event manager instance with the shared event
    manager instance at instantiation, and remove calls to `setSharedManager()`.
  - Adding test facilities to abstract obsolete `getEvent()` and
    `getListeners()` calls on event manager instances.

## 2.6.2 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.6.1 - 2015-09-22

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Fixed a condition where the `ModuleEvent` target was not properly populated
  with the `ModuleManager` as the target.

## 2.6.0 - 2015-09-22

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#10](https://github.com/zendframework/zend-modulemanager/pull/10) pins the
  zend-stdlib version to `~2.7`, allowing it to use that version forward, and
  ensuring compatibility with consumers of the new zend-hydrator library.

## 2.5.3 - 2015-09-22

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Fixed a condition where the `ModuleEvent` target was not properly populated
  with the `ModuleManager` as the target.

## 2.5.2 - 2015-09-22

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#9](https://github.com/zendframework/zend-modulemanager/pull/9) pins the
  zend-stdlib version to `>=2.5.0,<2.7.0`, as 2.7.0 deprecates the hydrators (in
  favor of the new zend-hydrator library).
