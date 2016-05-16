# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.7.2 - 2016-05-16

### Added

- [#38](https://github.com/zendframework/zend-modulemanager/pull/38) prepares
  and publishes the documentation to https://zendframework.github.io/zend-modulemanager/
- [#40](https://github.com/zendframework/zend-modulemanager/pull/40) adds a
  requirement on zend-config. Since the default use case centers around config
  merging and requires the component, it should be required by
  zend-modulemanager.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.7.1 - 2016-02-27

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#31](https://github.com/zendframework/zend-modulemanager/pull/31) updates the
  `ServiceListener:onLoadModulesPost()` workflow to override existing services
  on a given service/plugin manager instance when configuring it. Since the
  listener operates as part of bootstrapping, this is a requirement.

## 2.7.0 - 2016-02-25

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#13](https://github.com/zendframework/zend-modulemanager/pull/13) and
  [#28](https://github.com/zendframework/zend-modulemanager/pull/28) update the
  component to be forwards-compatible with zend-servicemanager v3. This
  primarily affects how configuration is aggregated within the
  `ServiceListener` (as v3 has a dedicated method in the
  `Zend\ServiceManager\ConfigInterface` for retrieving it).

- [#12](https://github.com/zendframework/zend-modulemanager/pull/12),
  [#28](https://github.com/zendframework/zend-modulemanager/pull/28), and
  [#29](https://github.com/zendframework/zend-modulemanager/pull/29) update the
  component to be forwards-compatible with zend-eventmanager v3. Primarily, this
  involves:
  - Changing trigger calls to `triggerEvent()` and/or `triggerEventUntil()`, and
    ensuring the event instance is injected with the new event name prior.
  - Ensuring aggregates are attached using the `$aggregate->attach($events)`
    signature instead of the `$events->attachAggregate($aggregate)` signature.
  - Using zend-eventmanager's `EventListenerIntrospectionTrait` to test that
    listeners are attached at expected priorities.

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
