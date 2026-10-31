# Changelog

All notable changes to `boldlygrow/audit-log` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2026-10-31

### Overview

This release drops support for Laravel 11, which is past the end of its security support. `AuditLog::create()`, the configuration, and the database schema are unchanged, so applications on Laravel 12 or 13 can upgrade without code changes.

The test suite now runs on PHP 8.2 through 8.5 with Laravel 12 and 13.

### Added

- **PHPStan types for `AuditLog::create()` and the model's date range scopes**
    - `create()` is documented as returning `array<string, mixed>`, and its `dump_keys`, `dump_strings`, `errors`, and `metadata` parameters document their value types.
    - The `createdBefore`, `createdAfter`, `occurredBefore`, `occurredAfter`, `deletedBefore`, and `deletedAfter` scopes are documented with `Builder<self>`.
    - **Why:** PHPStan and Larastan can check the values that your application passes to `create()` and reads from its response.

### Removed

- **Laravel 11 is no longer supported**
    - The `illuminate/*` constraints are `^12.0 || ^13.0`.
    - **Why:** Laravel 11's security support ended in March 2026. Every 11.x release has security advisories that Composer blocks from installing by default, so it can't be installed or tested without turning off Composer's security blocking.
    - **How:** Upgrade your application to Laravel 12 or 13 before you upgrade to `^3.0`. An application that must stay on Laravel 11 can stay on `^2.0`.

### Upgrade Steps

1. **Upgrade Laravel first.** Your application must run Laravel 12 or 13. Laravel 12 supports PHP 8.2 or newer, and Laravel 13 requires PHP 8.3 or newer.
2. **Update the package.** Change your constraint to `"boldlygrow/audit-log": "^3.0"`, and run `composer update boldlygrow/audit-log`.

No code, configuration, or migration changes are needed.

## [2.0.1] - 2026-07-22

### Changed

- **Release notes are consolidated in `CHANGELOG.md`**
    - The per-version `changelog/*` files were merged into this file, in the Keep a Changelog format.

### Fixed

- **`duration_ms` and `event_ms` accept a `CarbonImmutable`**
    - Both `AuditLog::create()` parameters are typed as `?CarbonInterface` instead of `?Carbon`, so any Carbon implementation is accepted.
    - Applications that use immutable dates (`Date::use(CarbonImmutable::class)`, which makes `now()` return a `CarbonImmutable`) got a `TypeError` when they passed `now()` to these parameters.
    - The parameters are only read to calculate a millisecond delta, so existing `Carbon` call sites are unaffected.
    - **How:** You can remove any `Carbon::instance()` conversion that you added to work around the old type.

## [2.0.0] - 2026-07-20

### Overview

This release moves the package to the `BoldlyGrow` namespace, renames the `Log` class to `AuditLog`, and adds database persistence.

- **The package is renamed** from `provisionesta/audit` to `boldlygrow/audit-log`. The `Log` class is renamed to `AuditLog`, so it no longer shadows Laravel's `Illuminate\Support\Facades\Log`.
- **Audit events can be stored in the database.** Events can be written to a queryable `audit_logs` table in addition to the system log, with a shipped Eloquent model and a publishable migration. This is the store that earlier releases described as "upcoming."
- **The event schema is broader and configurable.** It adds relationship fields, request origin tracking, and model-to-type calculation. The actor attributes, proxy IP headers, actor sources, and custom fields are configurable, so the package can adapt to an application's models without being forked.
- **Laravel v11, v12, and v13 are supported.** Support for Laravel v8, v9, and v10 is dropped.
- **1.x call sites keep working.** `provisionesta/audit` is kept as a Composer `replace` alias, the `Log` class remains as a deprecated subclass, and every new capability is opt-in with backward-compatible defaults.

### Added

#### Framework Support

- **Laravel v11, v12, and v13 are supported**
    - The `illuminate/*` constraints are `^11.0 || ^12.0 || ^13.0`, with PHP `^8.2`. Laravel v8, v9, and v10 are no longer supported (see **Removed**).

#### Database Persistence

- **Audit events can be persisted to the database**
    - Passing `database: true` writes the event to the Eloquent model in `config('audit-log.database.model')`, in addition to the system log.
    - Persistence is gated by `config('audit-log.database.enabled')`, which defaults to `true`.
    - **Why:** System logs are often rotated, sampled, or shipped to third parties with short retention. Compliance and forensic use cases need audit events kept indefinitely and queryable with SQL. This is the durable store that the 1.x README promised as an upcoming `audit_transactions` table.
    - **How:** Publish and run the migration (`php artisan vendor:publish --tag=audit-log`, then `php artisan migrate`), and add `database: true` to the events that you want to store. The `audit-log` tag publishes the config and the migration, and `--tag=audit-log-migrations` publishes only the migration.
- **A base model with polymorphic relationships is included**
    - `BoldlyGrow\AuditLog\Models\AuditLog` has `morphTo` relationships (`actor`, `parent`, `record`, `related`, `subject`, `tenant`) that are resolved from the stored fully-qualified class names.
    - It also has JSON and `datetime` casts, soft deletes, and a config-driven table name.
    - **Why:** Persistence works with no setup, and applications still get a model to query and relate.
    - **How:** To add your own relationships, casts, or scopes, create a model that extends the base model and set `config('audit-log.database.model')` to it. Bug fixes and new columns from the package still apply.
- **Date range query scopes**
    - The base model has `createdBefore`/`createdAfter`, `occurredBefore`/`occurredAfter`, and `deletedBefore`/`deletedAfter` scopes that accept any date that Carbon can parse. The `deleted*` scopes return only soft-deleted rows.
    - **Why:** Time-bounded lookups, such as the history of a record last quarter, retention sweeps, and incident windows, are the most common audit queries, and scopes keep those queries readable and consistent.
- **Configurable table name**
    - `config('audit-log.database.table')` sets the table for both the migration and the model.
    - **Why:** It avoids collisions with an existing `audit_logs` table, and lets applications follow their own naming conventions.
- **UUIDv7 primary keys and configurable identifier formats**
    - The primary key defaults to a timestamp-ordered UUIDv7, which the base model generates in its `creating` hook as a non-incrementing string key.
    - The polymorphic reference columns (`actor_id`, `record_id`, `parent_id`, `related_id`, `subject_id`, `tenant_id`) default to `CHAR(36)`, sized for a UUID or ULID. External identifiers (`*_provider_id`, `*_reference_*`, `actor_session_id`, and the job IDs) stay `VARCHAR`.
    - A comment block in the migration shows how to switch any ID column to `BIGINT`, `ULID`, or a custom format before you run `migrate`.
    - A `SAFETY CHECK` guard at the top of `up()` throws until you delete it, so `php artisan migrate` can't run with ID formats that you haven't reviewed. The guard is skipped under the test runner.
    - **Why:** UUIDv7 is the de facto identifier for logging and audit systems, since it's globally unique and timestamp-ordered, which keeps it index-friendly. Applications key their models differently, so the reference ID format is a deliberate one-time choice at install instead of a hard-coded assumption.
- **Sensitive fields are encrypted at rest**
    - The model casts `actor_email`, `actor_name`, `actor_username`, `attribute_value_old`, `attribute_value_new`, `parent_reference_value`, and `record_reference_value` as `encrypted`, and `metadata` as `encrypted:array`.
    - These columns are `TEXT`, since the ciphertext exceeds `VARCHAR(255)`. Every other column has an explicit cast (`string`, `integer`, `datetime`, or `array`).
    - **Why:** Audit records often capture PII and before and after values. Encrypting them reduces exposure if the database is compromised, and the model decrypts them transparently on read.
    - **How:** `errors` is a plain JSON array. To encrypt more fields, add `encrypted` casts on a model that extends the base model, and change those columns to `TEXT`.
- **Persistence fails gracefully**
    - If persistence is enabled but the table hasn't been migrated, or the configured model is invalid, a warning is logged and the system log entry is still written. The warning is `Audit Logs database table not found. Run the migration or disable the database integration in config/audit-log.php`.
    - **Why:** A logging utility must never abort the calling code because of its own misconfiguration. Persistence is on by default, so a new install that hasn't run the migration yet gets an actionable message instead of an exception.
- **Records are immutable by default**
    - `config('audit-log.immutable')` has separate `update` and `destroy` controls, which both default to `true`. When a control is `true`, the operation throws `BoldlyGrow\AuditLog\Exceptions\ImmutableRecordException`. When it's `false`, the operation is allowed and recorded.
    - A record can always be soft deleted. Only `forceDelete()` is gated by `destroy`.
    - Every mutation is written as a new audit entry (`audit.log.soft_deleted`, `audit.log.restored`, `audit.log.updated`, `audit.log.destroyed`) that is linked to the record and attributed to the acting user.
    - Each mutation and its audit entry run in one database transaction, so a record is never changed without its log. If either fails, both roll back.
    - `withJustification('...')` stores an optional reason on the audit entry. The rules are enforced by the `BoldlyGrow\AuditLog\Traits\ImmutableRecords` trait on the base model.
    - **Why:** Compliance frameworks such as SOC 2 and ISO 27001 expect audit trails to be non-repudiable. Recording every change, and requiring an explicit config change to allow one, makes tampering a deliberate, logged act instead of a quiet one.

#### Encrypted Field Search

- **Encrypted columns can be searched with query scopes**
    - The `BoldlyGrow\AuditLog\Traits\ModelEncryptedLookup` trait on the model makes the `encrypted` columns searchable. Laravel's encryption is non-deterministic, so a plain `where('actor_email', ...)` never matches.
    - The scopes decrypt in PHP, build a short-lived (two-minute) encrypted `id => value` index, and constrain the query to the matching keys. Each scope returns a query builder, so it chains with other constraints, ordering, and pagination.
    - String columns (`actor_email`, `actor_name`, `actor_username`, `attribute_value_old`, `attribute_value_new`, `parent_reference_value`, `record_reference_value`) have `whereEncryptedStringExact`, `whereEncryptedStringPartial`, `whereEncryptedStringStartsWith`, and `whereEncryptedStringEndsWith`.
    - The array column (`metadata`) has `whereEncryptedArraySearch`, `whereEncryptedArrayExact`, `whereEncryptedArrayPartial`, `whereEncryptedArrayStartsWith`, `whereEncryptedArrayEndsWith`, and the static `encryptedArrayKeys()` helper.
    - **Why:** Encrypting audit data at rest normally makes it unsearchable, which defeats the purpose of an audit store that you need to investigate.

Representative examples (see the [README](README.md#searching-encrypted-fields) for the full set, case-sensitivity rules, caching, and performance notes):

```php
use App\Models\AuditLog;

// Exact, case-insensitive match on an encrypted string column
$events = AuditLog::whereEncryptedStringExact('actor_email', 'jsmith@acme.com')->get();

// Partial match, chained with a normal constraint and pagination
$events = AuditLog::whereEncryptedStringPartial('actor_email', 'acme.com')
    ->where('event_type', 'okta.auth.login.error.invalid')
    ->orderByDesc('occurred_at')
    ->paginate(25);

// Search inside the encrypted metadata array
$events = AuditLog::whereEncryptedArraySearch('metadata', 'acme')->get();
$events = AuditLog::whereEncryptedArrayExact('metadata', 'approved_by', 'John Smith')->get();

// Discover which keys exist in the metadata column
$keys = AuditLog::encryptedArrayKeys('metadata');
```

#### Event Schema

- **`*_model` parameters calculate the matching `*_type`**
    - The new `parent_model`, `record_model`, `tenant_model`, `related_model`, and `subject_model` parameters accept a fully-qualified class name (`Model::class`). The snake_case `*_type` is calculated with the namespace in `config('audit-log.model.namespace')` (ex. `App\Models\Okta\User` becomes `okta_user`).
    - The `*_type` string parameters still work, and an explicit `*_type` overrides the calculated value when both are passed.
    - The migration indexes each `(*_type, *_id)` pair, so the snake_case types can be filtered for API queries, exports, and SIEM lookups without the class name. It also indexes each `(*_model, *_id)` pair, so `morphTo` relationships and `whereMorphedTo()` use an index.
    - **Why:** Writing `record_type: 'okta_user'` next to `record_id` is repetitive, and it drifts when models move. Passing the class is refactor-safe, and the `*_model` column drives the Eloquent relationships.
- **`related_*` and `subject_*` relationship fields**
    - `related_id` and `related_model` reference a related model that isn't a parent or child. `subject_id` and `subject_model` reference the impacted user, service account, or other audited model, based on your own business logic.
    - **Why:** Many events involve more than the authenticated user and one record (ex. user A changed a setting that affects service B on behalf of C). `subject_model` is generic, so any model in your application can be referenced.
- **`actor_source` records where a request came from**
    - It's detected as `system` (console or queue), `api` (an API route or JSON request), or `web`. `cli` and custom values can be passed explicitly.
    - The allowed values are in `config('audit-log.actor.source.allowed')`, and a value passed to `create()` is validated against them.
    - **Why:** The security posture of an event depends on where it came from. A session-based web action, a token-based API call, and an unattended background job are meaningfully different.

#### Configurability

- **Actor attributes are configurable**
    - The user model attributes that each actor field reads (`id`, `email`, `name`, `provider_id`, `username`) are mapped in `config('audit-log.actor.attributes')`. A mapping can be one attribute or an ordered list of candidates, and the first non-null value is used.
    - **Why:** Applications name their user columns differently (ex. `work_email`, `display_name`, `okta_id`), and these were hardcoded, which forced a fork or a wrapper. The list form also expresses the built-in `name` then `full_name` fallback in config instead of code.
- **Proxy IP headers are configurable**
    - `actor_ip_addr` checks the headers in `config('audit-log.actor.ip_headers')` (ex. `CF-Connecting-IP` or `X-Forwarded-For`) before it falls back to `request()->ip()`.
    - **Why:** Behind a CDN or reverse proxy, `request()->ip()` returns the proxy's address instead of the actor's, and the trusted headers are specific to each application.
- **Custom fields without forking**
    - Keys listed in `config('audit-log.database.custom_fields')` are copied from the `metadata` array into their own database columns, which you add with your own migration. They also stay in the `metadata` JSON for the system log.
    - **Why:** Applications often need to store and index their own fields, such as a tenant, organization, or workspace ID, with real columns and relationships, without changing the package or waiting for new parameters.

#### Tooling

- **Pest test suite**
    - A Testbench-backed Pest suite covers the logger, actor resolution, output formatting, database persistence, the base model, immutability, and encrypted field search. Run it with `composer test`.
    - **Why:** The 2.0 surface area is large enough to need automated regression tests, and a public test suite makes it easier to contribute.
- **Static analysis with Larastan (PHPStan)**
    - `phpstan.neon` is configured for level 9. Run it with `vendor/bin/phpstan analyse`.
    - **Why:** Static analysis catches type and null safety mistakes before they ship, which matters most for a utility that other packages and applications build on.

#### Documentation

- **The README is rewritten** for the full 2.0 parameter set, database persistence, custom fields, and the configurable actor, model, and source options.
- **Guidance for searching `*_type` strings**
    - The README explains when to query the snake_case `*_type` columns (ex. `okta_user`) with a plain `where()`, which is better for API filters, CSV and JSON exports, and SIEM ingestion, and when to use the fully-qualified `*_model` columns. The migration indexes every `*_type` column.
- **Example audit log API endpoints in `examples/`**
    - Copyable reference implementations of a read-only `list` and `describe` API come in two versions: `api-endpoints-no-dependency` (Laravel and Eloquent only) and `api-endpoints-spatie-data-query` (`spatie/laravel-query-builder` and `spatie/laravel-data`). The files are stubs that aren't autoloaded.
    - **Why:** A queryable audit log API is a common next step after enabling persistence, and this provides a starting point without requiring a particular query or serialization package.
- **`COMPLIANCE.md` maps the package to compliance controls**
    - It covers SOC 1 and SOC 2, ISO 27001, NIST SP 800-53, 800-63, and 800-171, CISA, and CIS controls, with an explicit shared responsibility model.
    - **Why:** Audit logging is often adopted to meet compliance obligations, and documenting what the package does and doesn't provide helps teams scope the remaining work honestly.

### Changed

- **Renamed from `provisionesta/audit` to `boldlygrow/audit-log`**
    - The PHP namespace changed from `Provisionesta\Audit` to `BoldlyGrow\AuditLog`.
    - The service provider was renamed from `Provisionesta\Audit\AuditServiceProvider` to `BoldlyGrow\AuditLog\AuditLogServiceProvider`, and it's registered with Laravel package discovery.
    - `provisionesta/audit` is kept as a `replace` alias for a smoother upgrade.
    - **Why:** The project is consolidating under the maintainer's `BoldlyGrow` organization, alongside the related Okta API client and actions packages.
- **The `Log` class is renamed to `AuditLog`**
    - The original `Log` class is kept as a deprecated subclass (see **Deprecated**).
    - **Why:** The old name shadowed Laravel's `Illuminate\Support\Facades\Log`, which forced an internal alias and made `Log::create()` ambiguous next to `Log::info()`.
    - **How:** Change your imports from `use BoldlyGrow\AuditLog\Log;` to `use BoldlyGrow\AuditLog\AuditLog;`, and your calls from `Log::create(...)` to `AuditLog::create(...)`.
- **The config file is renamed to `config/audit-log.php`**
    - It was `config/audit.php`.
    - **Why:** The file name matches the package name, and it doesn't collide with other packages or application config that use the generic `audit` name.
    - **How:** Rename your published `config/audit.php` to `config/audit-log.php`, and change `config('audit.*')` references to `config('audit-log.*')` (ex. `config('audit.dump.default')` becomes `config('audit-log.dump.default')`).
- **`illuminate/database` is required**
    - **Why:** Database persistence uses Eloquent. It's a lightweight component like the package's other `illuminate/*` dependencies, and the full framework still isn't required.
- **Log messages are prefixed with the class name only**
    - The prefix is `ApiClient Success` instead of `ApiClient::post Success`.
    - **Why:** The fully-qualified `method` is still in the log context, so the method name in the message was redundant and made messages harder to scan.
- **Dates in the log context use ISO 8601 Zulu time**
    - Date values such as `occurred_at` end with `Z` instead of a `+00:00` offset.
    - **Why:** The `Z` suffix is unambiguous, sorts consistently, and is the format that most log aggregation platforms expect.

### Deprecated

- **The `Log` class**
    - It's a deprecated subclass of `AuditLog`, so existing `Log::create(...)` calls keep working. It's marked `@deprecated` and will be removed in a future major version.
- **The `transaction` parameter**
    - It's a deprecated alias for the new `database` parameter, and either one turns on database persistence.
    - **Why:** The 1.x `transaction` flag referred to a database store that never shipped. `database` names the behavior accurately, and keeping `transaction` avoids breaking call sites that already set it.

### Removed

- **Laravel v8, v9, and v10, and PHP v8.0 and v8.1, are no longer supported**
    - The minimum framework was raised from Laravel 8 to Laravel 11, and the minimum PHP version from 8.0 to 8.2.
    - The model defines its casts with the `casts(): array` method, which Eloquent only honors on Laravel 11 and later. On Laravel 8–10 the method is ignored, so `errors` and `metadata` didn't cast to arrays, and `occurred_at` and `job_timestamp` didn't become `Carbon` instances.
    - **Why:** The old `^8.0` constraint claimed support that the package didn't have. Laravel 8, 9, and 10 were also past their security support, and running an audit logging feature on an unsupported framework is a compliance risk in itself.
    - **How:** Upgrade your application to Laravel 11 or newer and PHP 8.2 or newer before you adopt `boldlygrow/audit-log:^2.0`. Applications on Laravel 8–10 should stay on `provisionesta/audit:^1.3`.
- **The `AUDIT_ACTOR_ENABLED` environment variable**
    - Config toggles are booleans in `config/audit-log.php` instead of `env()` reads, so the `actor.enabled` config value replaces this variable.
    - **Why:** These are per-application switches that are set once, and keeping them in the committed, cache-friendly config file is simpler than threading them through environment variables.
    - **How:** If you set `AUDIT_ACTOR_ENABLED=false`, set `actor.enabled` to `false` in your published `config/audit-log.php`.

### Fixed

- **`dump_config` reads the `audit-log.dump.*` config**
    - It still referenced the pre-2.0 `config('audit.dump.*')` key. The config rename missed this lookup, so `dump_config` resolved to `null` and returned the unfiltered schema.
- **A `dump_config` schema filters the response to its `keys`**
    - Its keys were only applied when a `dump_keys` argument was also passed, so a named schema never narrowed the response. The configured keys are now applied on their own, as documented.

### Upgrade Steps

1. **Update the package.** Require `boldlygrow/audit-log:^2.0` in `composer.json`, and run `composer update`.
2. **Update the namespace.** Find and replace `Provisionesta\Audit` with `BoldlyGrow\AuditLog` across your code base.
3. **Rename the `Log` class.** Change `Log` references to `AuditLog` (`use ...\AuditLog;` and `AuditLog::create(...)`). This is optional in 2.0, since the deprecated `Log` alias still works, but it's recommended before the alias is removed in a future major version.
4. **Rename the config file.** Rename your published `config/audit.php` to `config/audit-log.php` (or republish it with `php artisan vendor:publish --tag=audit-log`), and change `config('audit.*')` references to `config('audit-log.*')`.
5. **(Optional) Turn on database persistence.** Publish and run the migration (`php artisan vendor:publish --tag=audit-log`, then `php artisan migrate`), and add `database: true` to the events that you want to store. Persistence is enabled by default, so set `config('audit-log.database.enabled')` to `false` if your application doesn't use a database. `transaction: true` still works, but `database: true` is preferred.
6. This is not an exhaustive list. Review the `README.md` for the latest syntax and usage guidelines.

*Releases prior to 2.0 were developed on GitLab as `provisionesta/audit`. The `!N` tokens in the entries below are GitLab merge request references, retained for historical accuracy.*

## [1.3.0] - 2025-04-27

### Added

- Add Laravel v12.x support

## [1.2.2] - 2024-11-22

### Fixed

- **The actor fields can be turned off with a config value** (!16)
    - When `config('audit.actor.enabled')` (`AUDIT_ACTOR_ENABLED`) is `false`, the `actor_*` fields aren't filled from the authenticated user and request. It defaults to `true`.

## [1.2.1] - 2024-11-20

### Removed

- **`server_hostname` is removed from the log context** (!14)
    - **Why:** It wasn't useful, and it isn't available on some systems.

## [1.2.0] - 2024-11-05

### Added

- **`actor_*` fields** (!11, !12)
    - `actor_email`, `actor_id`, `actor_ip_addr`, `actor_name`, `actor_provider_id`, `actor_session_id`, `actor_type`, and `actor_username` are filled from the authenticated user and the request. !12 fixed typos in the implementation.
- **`memory_current`, `memory_peak`, and `server_hostname` in the log context** (!12)

### Fixed

- **`*_id` values can be any string** (!10)
    - The validation rules no longer require a UUID.

## [1.1.0] - 2024-03-02

### Added

- **Background job parameters** (!4)
    - `job_batch`, `job_id`, `job_platform`, `job_pipeline_id`, `job_timestamp`, and `job_transaction_id`.
- **`Log::create()` returns an array with a custom schema** (!5)
    - The `dump_config`, `dump_date`, `dump_keys`, and `dump_strings` parameters set which keys and static strings the array includes.
- Add instructions to `CONTRIBUTING.md` for testing the package in an existing project (!7)

### Fixed

- Add `(optional)` to the `Log::create()` docblock for each optional parameter (!6)
- **`*_id` and `*_type` no longer have a `required_with` validation rule** (!8)

## [1.0.0] - 2023-12-29

### Overview

This initial release adds the `Log::create()` method and defines the standardized attributes.

### Added

- Add Laravel v11.x support
- Add PHP v8.3 support
- Add the log attributes
- Add validation for the attribute values
- Add `.gitlab-ci.yml` for CI/CD testing
- Add `README.md` with example usage

[3.0.0]: https://github.com/boldlygrow/audit-log/compare/2.0.1...3.0.0
[2.0.1]: https://github.com/boldlygrow/audit-log/compare/2.0...2.0.1
[2.0.0]: https://github.com/boldlygrow/audit-log/compare/1.3...2.0
[1.3.0]: https://github.com/boldlygrow/audit-log/compare/1.2.2...1.3
[1.2.2]: https://github.com/boldlygrow/audit-log/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/boldlygrow/audit-log/compare/1.2...1.2.1
[1.2.0]: https://github.com/boldlygrow/audit-log/compare/1.1...1.2
[1.1.0]: https://github.com/boldlygrow/audit-log/compare/1.0...1.1
[1.0.0]: https://github.com/boldlygrow/audit-log/releases/tag/1.0
