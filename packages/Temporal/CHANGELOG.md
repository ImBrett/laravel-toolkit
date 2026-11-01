# Changelog

All notable changes to `imbrett/laravel-temporal` will be documented in this file.

## 1.0.0 - 2025-10-26

- Temporal model base class with atomic versioning helpers (`upsertTemporal`, scoped queries).
- `HasTemporalAttributes` trait for surfacing history-backed attributes on aggregate models.
- Schema blueprint macros (`temporalColumns`, `temporalIndexes`) for consistent table layouts.
- `make:temporal` artisan command with publishable model and migration stubs.
- Configurable infinity marker and user resolver via `temporal.php`.
