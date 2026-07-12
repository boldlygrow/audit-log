# Audit and Event Log Handler

[[_TOC_]]

## Overview

The Audit Log package is an open source [Composer](https://getcomposer.org/) package for use in Laravel applications that is used by other [Boldly Grow](https://github.com/boldlygrow) packages and ventures including [Provisionr](https://provisionr.io) to provide a consistent log syntax and optional database persistence for audit events. Although this is purpose built for our packages, you are welcome to adopt this for your own standardized logging.

Please use at your own risk and create pull requests for any bugs that you encounter.

### Problem Statement

When using [Laravel Logging](https://laravel.com/docs/10.x/logging) with the `Log::info('Message', ['key1' => 'value', 'key2' => 'value'])` syntax, it is easy to have inconsistency with log formatting that results in a variety of log messages and varying context keys.

The `BoldlyGrow\AuditLog\AuditLog::create()` method provides a pre-defined set of context keys that allow us to improve indexing and searchability in external logging platforms, and ensures that all events provide as much context data as possible in a consistent format.

Sometimes you need to get a formatted array that can be added to a changelog or actioned upon programmatically instead of trying to tail a log file. An array is returned for each log entry that is created.

### Feature Comparison

How this package compares to Laravel's built-in logging and [Spatie Activity Log](https://spatie.be/docs/laravel-activitylog). Each capability links to the section that documents it.

**Legend:** ✅ built-in · ◐ partial or manual · ❌ not provided

| Capability | Laravel Logs | Spatie Activity Log | BoldlyGrow Audit Log |
|------------|:---:|:---:|:---:|
| **Records & storage** | | | |
| [Durable database table](#database-persistence) | ❌ | ✅ | ✅ |
| [System log-channel output](#basic-usage) | ✅ | ❌ | ✅ |
| [Configurable table & model](#database-persistence) | ❌ | ✅ | ✅ |
| [UUIDv7 primary keys](#publish-the-config-and-migration) | ❌ | ❌ | ✅ |
| [Soft deletes (recoverable)](#immutability) | ❌ | ❌ | ✅ |
| [Indexed for query performance](#simple-string-searches-with-_type) | ❌ | ◐ | ✅ |
| **Attribution** | | | |
| [Actor identity / causer](#actor-metadata) | ❌ | ✅ | ✅ |
| [Session, IP & request source](#actor-metadata) | ❌ | ❌ | ✅ |
| [Configurable actor attribute mapping](#mapping-custom-user-attributes) | ❌ | ◐ | ✅ |
| [Proxy / CDN IP resolution](#actor-ip-address-behind-a-proxy-or-cdn) | ❌ | ❌ | ✅ |
| **Event content** | | | |
| [Standardized structured schema](#log-parameter-definitions) | ❌ | ◐ | ✅ |
| [Event classification (type / level / outcome)](#comprehensive-usage) | ◐ | ◐ | ✅ |
| [State change (old / new values)](#log-parameter-definitions) | ❌ | ✅ | ✅ |
| [Affected-resource linkage](#model-references-and-automatic-types) | ❌ | ◐ | ✅ |
| [Model → snake_case type + FQCN](#model-references-and-automatic-types) | ❌ | ◐ | ✅ |
| [Background job / batch / pipeline metadata](#background-job-log-entry) | ❌ | ◐ | ✅ |
| **Automatic capture** | | | |
| Automatic model-change logging (trait) | ❌ | ✅ | ❌ |
| [Runs without a database (log-only)](#skipping-log-creation) | ✅ | ❌ | ◐ |
| **Security & compliance** | | | |
| [Encryption at rest](#encryption-at-rest) | ❌ | ❌ | ✅ |
| [Search encrypted fields](#searching-encrypted-fields) | ❌ | ❌ | ✅ |
| [Immutable records (block update / destroy)](#immutability) | ❌ | ❌ | ✅ |
| [Tamper-evident mutation recording](#immutability) | ❌ | ❌ | ✅ |
| [Compliance control mapping](COMPLIANCE.md) | ❌ | ❌ | ✅ |
| **Querying & output** | | | |
| [Date-range scopes](#date-range-scopes) | ❌ | ◐ | ✅ |
| [Relationship querying](#querying-by-relationship) | ❌ | ◐ | ✅ |
| [SIEM-friendly string-type search](#simple-string-searches-with-_type) | ❌ | ◐ | ✅ |
| [Custom indexable columns](#adding-custom-fields) | ❌ | ◐ | ✅ |
| [Formatted response array / DTO](#response-schema) | ❌ | ❌ | ✅ |
| [Configurable response schemas](#standardized-configurations-for-response-array) | ❌ | ❌ | ✅ |
| [Copyable API endpoint examples](#example-api-endpoints) | ❌ | ❌ | ✅ |

The two competitor-win rows are deliberate: Spatie Activity Log logs model changes **automatically** via a trait (this package favors explicit, intentional `AuditLog::create()` events), and Laravel's channel logging runs with **no database** (persistence here is optional — see [Skipping Log Creation](#skipping-log-creation)).

### Audit Record Completeness & Scope

A single event can capture every element that audit-record standards — NIST 800-53 **AU-3**, CIS **8.5**, ISO 27001 **A.8.15** — expect a record to contain:

| Required element | Package field(s) |
|------------------|------------------|
| What happened | `event_type`, `level`, `message`, `method` |
| When | `occurred_at`, `datetime` (ISO 8601 Zulu), `created_at` |
| Where / source | `actor_source`, `actor_ip_addr`, `actor_session_id`, `method` |
| Outcome | `event_type` result segment (e.g. `…success`, `…error.validation`), `level` |
| Who | `actor_id`, `actor_email`, `actor_name`, `actor_username`, `actor_provider_id`, `actor_type` |
| Affected resources + before/after | `record_*`, `parent_*`, `related_*`, `subject_*`, `tenant_*`, `attribute_value_old`, `attribute_value_new` |

**Scope — what this package does _not_ do.** It generates and stores records; it is not a full audit-management platform. You remain responsible for **retention scheduling**, **access control to the logs**, **review / alerting (SIEM)**, **time synchronization**, and **cryptographic tamper-evidence (hashing / signing)**. For how the package's capabilities map to compliance frameworks (SOC 1/2, ISO 27001, NIST 800-53/63/171, CISA, CIS) and the full shared-responsibility boundaries, see [COMPLIANCE.md](COMPLIANCE.md).

### Issue Tracking and Bug Reports

We do not maintain a roadmap of feature requests, however we invite you to contribute and we will gladly review your merge requests.

Please create an [issue](https://github.com/boldlygrow/audit-log/issues) for bug reports.

### Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) to learn more about how to contribute.

### Maintainers

| Name | GitLab Handle | Email |
|------|---------------|-------|
| [Jeff Martin](https://www.linkedin.com/in/jeffersonmmartin/) | [@jeffersonmartin](https://github.com/jeffersonmartin) | `jeff [at] boldlygrow [dot] us` |

### Contributor Credit

- Jeff Martin

## Installation

### Requirements

| Requirement | Version                     |
|-------------|-----------------------------|
| PHP         | `^8.2`                      |
| Laravel     | `^11.0`, `^12.0`, `^13.0`   |

### Upgrade Guide

See the [changelog](changelog/) for release notes. If you are upgrading from `provisionesta/audit` (1.x), read the [2.0 changelog](changelog/2.0.md) for the package rename, class rename, and configuration changes.

### Add Composer Package

```plain
composer require boldlygrow/audit-log:^2.0
```

The old `provisionesta/audit` package name is retained as a `replace` alias, so existing dependency graphs continue to resolve during the transition.

If you are contributing to this package, see [CONTRIBUTING.md](CONTRIBUTING.md) for instructions on configuring a local composer package with symlinks.

### Publish the config and migration

Publish both the config file and the migration together:

```plain
php artisan vendor:publish --tag=audit-log
```

Or publish them separately:

```plain
php artisan vendor:publish --tag=audit-log-config
php artisan vendor:publish --tag=audit-log-migrations
```

Publishing the config file (`config/audit-log.php`) is optional — the package works with sensible defaults and it is only needed to customize settings such as response schemas ([`dump_config`](#standardized-configurations-for-response-array)). The migration is required for [database persistence](#database-persistence), which is enabled by default.

**Before running the migration, review the identifier column formats.** The published migration defaults the primary key to **UUIDv7** (timestamp-ordered — the de facto standard for logging systems) and the polymorphic reference ids (`actor_id`, `record_id`, `parent_id`, `related_id`, `subject_id`, `tenant_id`) to `CHAR(36)`, sized for a UUID or ULID. An inline comment block at the top of the migration shows how to switch any of them to `BIGINT`, `ULID`, or a custom/`VARCHAR` format to match your own models — decide this now, since changing it later means an additional migration. If you change the primary key type, also update the base model's `boot()` id generation (override it on a model that extends the base).

To make sure this review is not skipped, the migration ships with a **`SAFETY CHECK` block at the top of its `up()` method that throws until you delete it** — `php artisan migrate` will fail with an explanatory message until you have reviewed the formats and removed that block. (The guard is skipped automatically under the test runner, so it does not affect your test suite.) Once you have removed it, run:

```plain
php artisan migrate
```

### Bundled Examples

The package ships copyable reference implementations in the [`examples/`](examples) directory — including two variants of a read-only audit log API (`list` + `describe`), one dependency-free and one using Spatie Query Builder + Laravel Data. They are reference stubs (not autoloaded) to copy into your app and adapt. See [Example API Endpoints](#example-api-endpoints) for details.

## Usage Examples

### Basic Usage

```php
use BoldlyGrow\AuditLog\AuditLog;

AuditLog::create(
    event_ms: $event_ms,
    event_type: 'okta.api.post.success.ok',
    level: 'info',
    message: 'Success',
    metadata: [
        'okta_request_id' => 'REDACTED',
        'rate_limit_remaining' => $request->headers['x-rate-limit-remaining'],
        'uri' => 'users',
        'url' => 'https://dev-12345678.okta.com/api/v1/users?activate=true'
    ],
    method: __METHOD__
);
```

### Comprehensive Usage

You can copy and paste this example anywhere in your code that you would create a log entry. Any arguments that are not relevant can be removed and will be considered null.

The `actor_*` fields are intentionally omitted below — they are automatically populated from the authenticated user based on your configuration. See [Actor Metadata](#actor-metadata) to customize, map, or override them.

```php
use BoldlyGrow\AuditLog\AuditLog;

AuditLog::create(
    attribute_key: 'xxx',
    attribute_value_old: 'xxx',
    attribute_value_new: 'xxx',
    count_records: count($array),
    database: false,
    dump_config: null,
    dump_date: 'c',
    dump_keys: [],
    dump_strings: [],
    duration_ms: $duration_ms,
    duration_ms_per_record: (int) ($duration_ms / count($records)),
    errors: [],
    event_ms: $event_ms,
    event_ms_per_record: (int) ($event_ms / count($records)),
    event_type: '{provider}.{entity}.{action}.xxx',
    level: 'info',
    log: true,
    message: '{What happened}',
    metadata: [],
    method: __METHOD__,
    occurred_at: $entity->created_at,
    parent_id: $parent->id,
    parent_model: ProviderApplication::class, // parent_type => "provider_application"
    parent_provider_id: $parent->provider_id,
    parent_reference_key: 'name',
    parent_reference_value: $entity->organization->name,
    record_id: $entity->id,
    record_model: ProviderEntity::class,      // record_type => "provider_entity"
    record_provider_id: $entity->provider_id,
    record_reference_key: 'name',
    record_reference_value: $entity->name,
    related_id: $manager->id,
    related_model: ProviderUser::class,
    subject_id: $service->id,
    subject_model: ProviderService::class,
    tenant_id: $entity->provider_organization_id,
    tenant_model: ProviderOrganization::class,
);
```

### Example Output

```plain
[YYYY-MM-DD HH:II:SS] local.DEBUG: ApiClient Success {"event_type":"okta.api.post.success.ok","method":"BoldlyGrow\\Okta\\ApiClient::post","event_ms":627,"metadata":{"okta_request_id":"REDACTED","rate_limit_remaining":"16","uri":"users","url":"https://dev-12345678.okta.com/api/v1/users?activate=true"}"}
```

> The log message is prefixed with the class name only (`ApiClient`). The fully-qualified `method` remains in the log context.

```json
{
    "event_type": "okta.api.post.success.ok",
    "method": "BoldlyGrow\\Okta\\ApiClient::post",
    "event_ms": 627,
    "metadata": {
        "okta_request_id": "REDACTED",
        "rate_limit_remaining": "16",
        "uri": "users",
        "url": "https://dev-12345678.okta.com/api/v1/users?activate=true"
    }
}
```

### Log Parameter Definitions

> The `actor_*` fields are auto-populated from the authenticated user using the attribute map in `config('audit-log.actor.attributes')`, so you normally do not pass them. Provide an `actor_*` argument only to **override** the automatic value. For those rows, the **Example Usage** column shows the default source of the value. See [Actor Metadata](#actor-metadata) for mapping and overriding.

<table>
<thead>
<tr>
<th>Parameter Name</th>
<th>Example Usage</th>
<th>Description</th>
</tr>
</thead>
<tbody>
<tr>
<td><strong>event_type</strong> (Required)<br /><code>string</code></td>
<td><code>provider.entity.action.result.reason</code></td>
<td>The octet notation event type that follows our codestyle conventions.</td>
</tr>
<tr>
<td><strong>level</strong> (Required)<br /><code>string</code></td>
<td><code>debug</code><br /><code>info</code><br /><code>notice</code><br /><code>warning</code><br /><code>error</code><br /><code>critical</code><br /><code>alert</code><br /><code>emergency</code></td>
<td>The log level for the log entry</td>
</tr>
<tr>
<td><strong>message</strong> (Required)<br /><code>string</code></td>
<td>Validation Failed</td>
<td>A short message to include in the logs. This will be auto-prefixed
     with the fully-qualified method name (the "noun") so you can keep
     the message focused on the "verb" language.</td>
</tr>
<tr>
<td><strong>method</strong> (Required)<br /><code>string</code></td>
<td><code>__METHOD__</code></td>
<td>The method where this audit log is created in or is on behalf of.</td>
</tr>
<tr>
<td>actor_email<br><code>string</code></td>
<td><code>email</code> attribute (default)</td>
<td>The email address of the actor. Auto-populated from the user; pass to override.</td>
</tr>
<tr>
<td>actor_id<br><code>string</code></td>
<td><code>id</code> attribute (default)</td>
<td>The database ID of the actor. Auto-populated from the user; pass to override.</td>
</tr>
<tr>
<td>actor_name<br><code>string</code></td>
<td><code>name</code> ?? <code>full_name</code> (default)</td>
<td>The first and last name of the actor. Auto-populated from the user; pass to override.</td>
</tr>
<tr>
<td>actor_provider_id<br><code>string</code></td>
<td><code>provider_id</code> attribute (default)</td>
<td>The 3rd party vendor API ID of the actor (ex. Okta User ID). Auto-populated from the user; pass to override.</td>
</tr>
<tr>
<td>actor_session_id<br><code>string</code></td>
<td><code>session()->getId()</code></td>
<td>The session ID of the actor. Auto-populated; pass to override.</td>
</tr>
<tr>
<td>actor_source<br><code>string</code></td>
<td><code>system</code> / <code>api</code> / <code>web</code> (auto-detected)</td>
<td>The origin of the request. Auto-detected when omitted, or pass <code>cli</code>/a custom value explicitly; see <a href="#actor-source">Actor Source</a>. Validated against <code>config('audit-log.actor.source.allowed')</code>.</td>
</tr>
<tr>
<td>actor_type<br><code>string</code></td>
<td>authenticated user model class</td>
<td>The fully-qualified class name of the authenticated user model. Auto-populated; pass to override.</td>
</tr>
<tr>
<td>actor_username<br><code>string</code></td>
<td><code>username</code> attribute (default)</td>
<td>The username of the actor. Auto-populated from the user; pass to override.</td>
</tr>
<tr>
<td>attribute_key<br /><code>string</code></td>
<td></td>
<td>(State Changes) The database column name that has changed.</td>
</tr>
<tr>
<td>attribute_value_old<br /><code>string</code></td>
<td></td>
<td>(State Changes) The value in the database before the update.</td>
</tr>
<tr>
<td>attribute_value_new<br /><code>string</code></td>
<td></td>
<td>(State Changes) The API value that is now updated in the database.</td>
</tr>
<tr>
<td>count_records<br /><code>int</code></td>
<td><code>count($array)</code></td>
<td>(Multiple records) Count of records processed.</td>
</tr>
<tr>
<td>database<br /><code>bool</code></td>
<td><code>true</code><br /><code>false</code> (default)</td>
<td>Whether to persist this event to the database. See <a href="#database-persistence">Database Persistence</a>.</td>
</tr>
<tr>
<td>dump_config<br /><code>string</code></td>
<td><code>default</code></td>
<td>(<a href="#response-schema">Response Schema</a>) The array key in <code>config/audit-log.php</code> that contains the <code>date</code>, <code>keys</code>, <code>strings</code> <a href="#standardized-configurations-for-response-array">schema configuration</a>. The other <code>dump_*</code> parameters are ignored if <code>dump_config</code> is set.</td>
</tr>
<tr>
<td>dump_date<br /><code>string</code></td>
<td><code>c</code><br><code>Y-m-d</code><br><code>Y-m-d H:i:s</code></td>
<td>(<a href="#response-schema">Response Schema</a>) The <a target="_blank" href="https://www.php.net/manual/en/datetime.format.php">PHP datetime format</a> string for timestamps returned in the response array. </td>
</tr>
<tr>
<td>dump_keys<br /><code>array</code></td>
<td>See <a href="#response-schema">docs</a></td>
<td>(<a href="#response-schema">Response Schema</a>) An filtered list of array of keys from the <code>AuditLog::create()</code> method that returned in the response array.</td>
</tr>
<tr>
<td>dump_strings<br /><code>array</code></td>
<td>See <a href="#response-schema">docs</a></td>
<td>(<a href="#response-schema">Response Schema</a>) An array of key value pairs of static strings that should be included in the in the response array (instead of having to add them yourself with collection transformation later).</td>
</tr>
<tr>
<td>duration_ms<br /><code>Carbon</code></td>
<td><code>$duration_ms</code></td>
<td>Carbon instance (timestamp) used for long running batch jobs to provide a point-in-time duration since job started.</td>
</tr>
<tr>
<td>duration_ms_per_record<br /><code>int</code></td>
<td><code>(int) ($duration_ms / count($records))</code></td>
<td>Number of milliseconds divided by count of records. This is not auto-calculated to allow flexibility for custom Carbon timestamps</td>
</tr>
<tr>
<td>errors<br /><code>array</code></td>
<td></td>
<td>Flat array of error message(s) that will be encoded as JSON</td>
</tr>
<tr>
<td>event_ms<br /><code>Carbon</code></td>
<td><code>$event_ms</code></td>
<td>Carbon instance (timestamp) that was initialized at the start of the action and provides a point-in-time duration for this specific action within a longer running job.</td>
</tr>
<tr>
<td>event_ms_per_record<br /><code>int</code></td>
<td><code>(int) ($event_ms / count($records))</code></td>
<td>Number of milliseconds divided by count of records. This is not auto-calculated to allow flexibility for custom Carbon timestamps</td>
</tr>
<tr>
<td>job_batch<br /><code>string</code></td>
<td></td>
<td>(<a href="#background-job-log-entry">Background Job Logs</a>) The human identifier string or system ID of the batch of jobs. Format is at your discretion.</td>
</tr>
<tr>
<td>job_id<br /><code>string</code></td>
<td></td>
<td>(<a href="#background-job-log-entry">Background Job Logs</a>) The human identifier string or system ID of the specific job that triggered this log entry. Format is at your discretion.</td>
</tr>
<tr>
<td>job_platform<br /><code>string</code></td>
<td><code>github</code><br><code>gitlab</code><br><code>lambda</code><br><code>redis</code><br><code>{your string}</code></td>
<td>(<a href="#background-job-log-entry">Background Job Logs</a>) The human identifier string of the platform that the background jobs are running in. Format is at your discretion.</td>
</tr>
<tr>
<td>job_pipeline_id<br /><code>string</code></td>
<td></td>
<td>(<a href="#background-job-log-entry">Background Job Logs</a>) The system ID of the CI/CD pipeline (if applicable).</td>
</tr>
<tr>
<td>job_timestamp<br /><code>string</code></td>
<td><code>now()->getTimestamp()</code></td>
<td>(<a href="#background-job-log-entry">Background Job Logs</a>) The timestamp that the job or pipeline was started. This is useful for identifying which scheduled job timestamp triggered this event.</td>
</tr>
<tr>
<td>job_transaction_id<br /><code>string</code></td>
<td><code>now()->getTimestamp()</code></td>
<td>(<a href="#background-job-log-entry">Background Job Logs</a>) An alternative to <code>job_id</code> that can be used for additional indexable identifiers used by your application or business logic.</td>
</tr>
<tr>
<td>log<br /><code>bool</code></td>
<td><code>true</code> (default)<br /><code>false</code></td>
<td>Whether to create a system log entry for this event. See <a href="#skipping-log-creation">docs</a>.</td>
</tr>
<tr>
<td>metadata<br /><code>array</code></td>
<td></td>
<td>An array of custom metadata that should be included in the log</td>
</tr>
<tr>
<td>occurred_at<br /><code>string</code></td>
<td><code>2023-02-01T03:45:27.612584Z</code></td>
<td>A datetime that will be formatted with Carbon for when the event occurred at based on a created_at or updated_at API timestamp</td>
</tr>
<tr>
<td>parent_id<br /><code>string</code></td>
<td><code>{uuid}</code></td>
<td>(Many-to-Many Relationship Events) The database ID of the database model with a many-to-many relationship.</td>
</tr>
<tr>
<td>parent_type<br /><code>string</code></td>
<td><code>App\Models\Path\To\ModelName</code></td>
<td>(Many-to-Many Relationship Events) The fully-qualified namespace of the database model with a many-to-many relationship.</td>
</tr>
<tr>
<td>parent_model<br /><code>string</code></td>
<td><code>ProviderApplication::class</code></td>
<td>(Many-to-Many Relationship Events) The model class name. Auto-calculates <code>parent_type</code>; see <a href="#model-references-and-automatic-types">Model References</a>.</td>
</tr>
<tr>
<td>parent_provider_id<br /><code>string</code></td>
<td><code>a1b2c3d4e5f6</code></td>
<td>(Many-to-Many Relationship Events) The API ID of the database model with a many-to-many relationship that is usually stored in the database in the `provider_id` column.</td>
</tr>
<tr>
<td>parent_reference_key<br /><code>string</code></td>
<td><code>name</code></td>
<td>(Many-to-Many Relationship Events) The database column name for value that is human readable in logs</td>
</tr>
<tr>
<td>parent_reference_value<br /><code>string</code></td>
<td></td>
<td>(Many-to-Many Relationship Events) The value of the human readable database column</td>
</tr>
<tr>
<td>record_id<br /><code>string</code></td>
<td><code>{uuid}</code></td>
<td>The database ID of the affected database model</td>
</tr>
<tr>
<td>record_type<br /><code>string</code></td>
<td><code>App\Models\Path\To\ModelName</code></td>
<td>The fully-qualified namespace of the database model</td>
</tr>
<tr>
<td>record_model<br /><code>string</code></td>
<td><code>ProviderEntity::class</code></td>
<td>The model class name of the affected record. Auto-calculates <code>record_type</code>; see <a href="#model-references-and-automatic-types">Model References</a>.</td>
</tr>
<tr>
<td>record_provider_id<br /><code>string</code></td>
<td><code>a1b2c3d4e5f6</code></td>
<td>The API ID of the affected database model that is usually stored in the database in the `provider_id` column.</td>
</tr>
<tr>
<td>record_reference_key<br /><code>string</code></td>
<td><code>name</code></td>
<td>The database column name for value that is human readable in logs</td>
</tr>
<tr>
<td>record_reference_value<br /><code>string</code></td>
<td></td>
<td>The value of the human readable database column</td>
</tr>
<tr>
<td>related_id<br /><code>string</code></td>
<td><code>{uuid}</code></td>
<td>The database ID of a related model that should be referenced in logs.</td>
</tr>
<tr>
<td>related_model<br /><code>string</code></td>
<td><code>ProviderUser::class</code></td>
<td>The model class name of a related model that should be referenced in logs. Auto-calculates <code>related_type</code>.</td>
</tr>
<tr>
<td>subject_id<br /><code>string</code></td>
<td><code>{uuid}</code></td>
<td>The database ID of the impacted human user, service account, or other audited subject.</td>
</tr>
<tr>
<td>subject_model<br /><code>string</code></td>
<td><code>ProviderService::class</code></td>
<td>The model class name of the subject. Kept generic so any application model may be used. Auto-calculates <code>subject_type</code>.</td>
</tr>
<tr>
<td>tenant_id<br /><code>string</code></td>
<td><code>{uuid}</code></td>
<td>The database ID of the top-level organization/tenant for the provider</td>
</tr>
<tr>
<td>tenant_type<br /><code>string</code></td>
<td><code>App\Models\VendorName\Organization</code></td>
<td>The fully-qualified namespace of the database model of the top-level entity (organization, tenant, etc) for the provider.</td>
</tr>
<tr>
<td>tenant_model<br /><code>string</code></td>
<td><code>ProviderOrganization::class</code></td>
<td>The model class name of the top-level entity. Auto-calculates <code>tenant_type</code>.</td>
</tr>
<tr>
<td>transaction<br /><code>bool</code> (deprecated)</td>
<td><code>true</code><br /><code>false</code> (default)</td>
<td>Deprecated alias for <code>database</code>. Either flag persists the event. Prefer <code>database</code>.</td>
</tr>
</tbody>
</table>

## Advanced Usage

### Actor Metadata

The following fields related to the actor (authenticated user) are captured with each log entry.

This uses `Auth::user()` that uses the model configured in the `providers` array in `config/auth.php`. The Laravel default is `App\Models\User::class`, however your application may use a different model.

#### Mapping Custom User Attributes

If your user model uses different column names (for example `work_email` or `display_name`), map each actor field to the attribute(s) it should read in `config('audit-log.actor.attributes')`. A mapping may be a single attribute name or an ordered list of candidates, in which case the first non-null value wins (this is how `name` falls back to `full_name` by default).

```php
// config/audit-log.php
'actor' => [
    'attributes' => [
        'id' => 'id',
        'email' => 'work_email',
        'name' => ['display_name', 'name', 'full_name'],
        'provider_id' => 'okta_id',
        'username' => 'handle',
    ],
],
```

| Attribute           | Authenticated (`Auth::check()`)                 | Unauthenticated      |
|---------------------|-------------------------------------------------|----------------------|
| `actor_email`       | `Auth::user()->email`                           | null                 |
| `actor_id`          | `Auth::user()->id`                              | null                 |
| `actor_ip_addr`     | proxy header or `request()->ip()`               | proxy header or `request()->ip()` |
| `actor_name`        | `Auth::user()->name ?? Auth::user()->full_name` | null                 |
| `actor_provider_id` | `Auth::user()->provider_id`                     | null                 |
| `actor_session_id`  | `session()->getId()`                            | `session()->getId()` |
| `actor_source`      | `system` / `api` / `web` (auto-detected)        | `system` / `api` / `web` |
| `actor_type`        | `config('auth.providers.users.model')`          | null                 |
| `actor_username`    | `Auth::user()->username`                        | null                 |

#### Actor IP Address Behind a Proxy or CDN

If your application is behind a proxy or CDN, add the trusted header(s) that carry the originating IP to `config('audit-log.actor.ip_headers')`. The first non-empty header wins, falling back to `request()->ip()`.

```php
// config/audit-log.php
'actor' => [
    'ip_headers' => [
        'CF-Connecting-IP',
        'X-Forwarded-For',
    ],
],
```

#### Actor Source

The `actor_source` field records the origin of the request. It is auto-detected as `system` (console commands and queued jobs), `api` (API routes or requests that expect JSON), or `web`. The `cli` value is available for applications that detect device-based tokens, and must be passed explicitly.

The allowed vocabulary lives in one place. Add your own values (for example `service`) there, and any value passed to `AuditLog::create(actor_source: '...')` is validated against the list.

```php
// config/audit-log.php
'actor' => [
    'source' => [
        'enabled' => true,
        'allowed' => ['system', 'cli', 'api', 'web'],
    ],
],
```

The table above shows the defaults that are auto-populated for each actor field. You can override any of them by passing the corresponding argument to `AuditLog::create()`.

```php
use BoldlyGrow\AuditLog\AuditLog;

AuditLog::create(
    // ...
    actor_email: '{string}',
    actor_id: '{string}',
    actor_ip_addr: '{string}',
    actor_name: '{string}',
    actor_provider_id: '{string}',
    actor_session_id: '{string}',
    actor_source: '{string}',
    actor_type: '{string}',
    actor_username: '{string}',
    // ...
);
```

### Disabling Actor Metadata

Actor metadata is enabled by default. You can disable actor metdata if you do not want to capture actor metadata automatically or your application does not support request and session data (ex. Laravel Zero CLI app).

Set the `enabled` flag to `false` in `config/audit-log.php`:

```diff
    'actor' => [
-        'enabled' => true,
+        'enabled' => false,
    ],
```

### Background Job Log Entry

You can add the `job_*` parameters if you are running background jobs and want to add metadata to your logs and persisted database records. All of these values (except `job_timestamp`) are freeform strings that you can standardize however you'd like.

```php
use BoldlyGrow\AuditLog\AuditLog;

AuditLog::create(
    // ...
    job_batch: '{string}',
    job_id: '{string}',
    job_platform: '{string}',
    job_pipeline_id: '{string}',
    job_timestamp: now()->getTimestamp(),
    job_transaction_id: '{string}',
    // ...
);
```

### Model References and Automatic Types

Each relationship (`parent`, `record`, `related`, `subject`, `tenant`) accepts a fully-qualified model class name via a `*_model` parameter. The paired `*_type` value is auto-calculated as a snake_case string using the namespace configured in `config('audit-log.model.namespace')` (default `App\Models\`).

```php
use BoldlyGrow\AuditLog\AuditLog;

AuditLog::create(
    // ...
    record_id: $user->id,
    record_model: \App\Models\Okta\User::class, // record_type => "okta_user"
    related_id: $manager->id,
    related_model: \App\Models\Okta\User::class,
    subject_id: $service->id,
    subject_model: \App\Models\Service::class,  // any module's model may be used
    // ...
);
```

The legacy string parameters (`record_type: 'App\\Models\\Okta\\User'`) still work for backwards compatibility. When both are provided, an explicit `*_type` overrides the value calculated from `*_model` — so you can point the `*_model` column at a class while logging a custom `*_type` key.

### Simple String Searches with `*_type`

The [`whereMorphedTo()` / `whereHasMorph()` relationship queries](#querying-by-relationship) operate on the FQCN `*_model` columns (and `actor_type`) — pass a **class or model instance**, not the snake_case string.

For everything else, prefer the paired `*_type` column. It stores a stable, human-friendly snake_case string (`okta_user`) rather than a PHP class name (`App\Models\Okta\User`), so it is a plain column you can match with `where()` or raw SQL:

```php
$events = AuditLog::where('record_type', 'okta_user')->get();
```

This is the better fit whenever the consumer is not PHP:

- **API filters** — clients pass `record_type=okta_user`, not a fully-qualified class string. The [example API endpoints](#example-api-endpoints) expose exactly this.
- **CSV / JSON exports and SIEM ingestion** — downstream tools search and correlate on `okta_user` without needing to know your namespace, and the value stays stable even if the underlying model class is later moved or renamed.
- **Records whose model no longer exists** — the string is preserved regardless of whether the class is still resolvable.

> `actor_type` is the exception: it stores the FQCN (there is no `actor_model` column), so match it with the class string (`where('actor_type', User::class)`).

**Indexing.** Every `*_type` column is indexed by the shipped migration as a compound `(*_type, *_id)` index (for `actor`, `record`, `parent`, `related`, `subject`, and `tenant`). A bare `where('record_type', ...)` still uses the index via its leftmost prefix, and a `where('record_type', ...)->where('record_id', ...)` "history of one record" lookup is fully covered — no extra work on your part. The `(*_model, *_id)` columns are indexed too, so the [relationship queries](#querying-by-relationship) (`whereMorphedTo()` / morphTo eager-loading) are index-backed as well.

### Database Persistence

In addition to writing to the system log, entries can be persisted to a database table for perpetual audit storage and querying.

1. Publish and run the migration (skip the publish if you already ran `vendor:publish --tag=audit-log`):

   ```plain
   php artisan vendor:publish --tag=audit-log
   php artisan migrate
   ```

2. Pass `database: true` on the events you want to persist:

   ```php
   use BoldlyGrow\AuditLog\AuditLog;

   AuditLog::create(
       // ...
       database: true,
       // ...
   );
   ```

Database persistence is **enabled by default** via `config('audit-log.database.enabled')`; set it to `false` if your application does not use a database. Entries are stored using the package's `BoldlyGrow\AuditLog\Models\AuditLog` model by default.

To add relationships, casts, or scopes — for example to reference the audit log from your UI or API — create your own model that extends the base model and point `config('audit-log.database.model')` at it:

```php
namespace App\Models;

use BoldlyGrow\AuditLog\Models\AuditLog as BaseAuditLog;

class AuditLog extends BaseAuditLog
{
    // Add your relationships, casts, and scopes here.
}
```

The table name is configurable via `config('audit-log.database.table')`. If persistence is enabled but the table has not been migrated (or the configured model is invalid), a warning is logged and the event is still written to the system log — a misconfiguration never prevents your code from completing.

#### Encryption at Rest

The shipped model encrypts several columns that commonly hold PII or changed values — `actor_email`, `actor_name`, `actor_username`, `attribute_value_old`, `attribute_value_new`, `parent_reference_value`, `record_reference_value`, and `metadata` — using Laravel's `encrypted` casts. Those columns are stored as `TEXT`, and the model decrypts them transparently on read. This protects the database copy only; the same values are **not** encrypted in the system log channel. Encryption requires your application's `APP_KEY` to be set. To encrypt additional columns, add `encrypted` casts on a model that extends the base and switch the corresponding columns to `TEXT`.

#### Immutability

Audit trails are frequently required to be tamper-evident, so persisted records are **immutable by default**. Two independent controls in `config('audit-log.immutable')` govern this:

```php
'immutable' => [
    'update' => true,   // block updates to an existing record
    'destroy' => true,  // block permanent deletion (forceDelete)
],
```

**When a control is `true`**, the operation throws `BoldlyGrow\AuditLog\Exceptions\ImmutableRecordException`:

```php
$log->update(['message' => 'changed']); // throws when immutable.update is true
$log->forceDelete();                     // throws when immutable.destroy is true
```

**A record may always be soft deleted** — this is never blocked (`destroy` only gates `forceDelete()`), so you can hide/expire entries while keeping them recoverable:

```php
$log->delete();   // always allowed; the record is retained (soft deleted)
```

**Every mutation is itself recorded** as a new audit entry — including who did it (resolved through the standard actor pipeline) — so the trail is self-describing. Soft deletes and restores are always recorded; updates and force deletes are recorded when their control is `false` (i.e. allowed):

| Operation | `immutable.*` | Result | Recorded event |
|-----------|---------------|--------|----------------|
| Soft delete (`delete()`) | — | always allowed | `audit.log.soft_deleted` |
| Restore (`restore()`) | — | always allowed | `audit.log.restored` |
| Update | `update: true` | throws | — |
| Update | `update: false` | allowed | `audit.log.updated` |
| Force delete (`forceDelete()`) | `destroy: true` | throws | — |
| Force delete (`forceDelete()`) | `destroy: false` | allowed | `audit.log.destroyed` |

The meta entry links back to the mutated record via `record_id` / `record_model`.

**Atomicity.** Each mutation and the entry it records run in a single database transaction, so a record is never changed without its log — if writing the audit entry fails, the mutation rolls back with it.

**Justification.** For compliance workflows you can attach a reason that is stored on the meta entry's `metadata` (encrypted at rest):

```php
$log->withJustification('Legal hold released, ticket #4567')->delete();
```

> Because these controls are enforced on the model, they apply to any model that extends `BoldlyGrow\AuditLog\Models\AuditLog`. Turning a control off is a deliberate, auditable act — the change from `true` to `false` is itself the thing your compliance process should gate.

### Searching Encrypted Fields

The [encrypted columns](#encryption-at-rest) — `actor_email`, `actor_name`, `actor_username`, `attribute_value_old`, `attribute_value_new`, `parent_reference_value`, `record_reference_value`, and the `metadata` array — are stored as ciphertext. Laravel's encryption is **non-deterministic** (encrypting the same value twice produces different ciphertext), so a normal SQL query can never match them:

```php
// Never matches — the column holds ciphertext, not the plaintext
AuditLog::where('actor_email', 'jsmith@acme.com')->get();
```

The base model uses the `BoldlyGrow\AuditLog\Traits\ModelEncryptedLookup` trait, which adds query scopes that decrypt the column in PHP, build a cached `id => value` lookup index, and constrain the query to the matching primary keys.

Throughout this section, `AuditLog` refers to **your Eloquent model** — either the base `BoldlyGrow\AuditLog\Models\AuditLog` or your own `App\Models\AuditLog` that extends it. Every scope returns an Eloquent query builder, so you can chain `->get()`, `->first()`, `->count()`, `->paginate()`, `->orderBy()`, and additional `->where()` constraints just like a normal query.

#### Available Scopes

| Scope | Description | Example |
|-------|-------------|---------|
| `whereEncryptedStringExact($column, $value)` | Exact, case-insensitive match on an encrypted string column | [Example](#exact-string-match) |
| `whereEncryptedStringPartial($column, $value)` | Case-insensitive substring match anywhere in the value | [Example](#partial-string-match) |
| `whereEncryptedStringStartsWith($column, $value)` | Value begins with the term | [Example](#starts-with-and-ends-with) |
| `whereEncryptedStringEndsWith($column, $value)` | Value ends with the term | [Example](#starts-with-and-ends-with) |
| `whereEncryptedArraySearch($column, $search)` | Substring match across every key and value of an encrypted array column | [Example](#search-the-entire-array) |
| `whereEncryptedArrayExact($column, $key, $value)` | Exact match for a specific array key | [Example](#match-a-specific-array-key) |
| `whereEncryptedArrayPartial($column, $key, $value)` | Substring match for a specific array key | [Example](#match-a-specific-array-key) |
| `whereEncryptedArrayStartsWith($column, $key, $value)` | A specific array key begins with the term | [Example](#match-a-specific-array-key) |
| `whereEncryptedArrayEndsWith($column, $key, $value)` | A specific array key ends with the term | [Example](#match-a-specific-array-key) |
| `encryptedArrayKeys($column)` (static) | List the distinct keys present in an encrypted array column | [Example](#listing-array-keys) |

Every scope except `whereEncryptedArraySearch` accepts a final `bool $cache = true` argument — pass `false` to rebuild the index from fresh data. See [Caching and Fresh Data](#caching-and-fresh-data).

#### String Column Searches

#### Exact String Match

`whereEncryptedStringExact()` returns records whose decrypted value equals your term. Matching is **case-insensitive**.

```php
use App\Models\AuditLog;

// Every event attributed to a specific email address
$events = AuditLog::whereEncryptedStringExact('actor_email', 'jsmith@acme.com')->get();

// Case does not matter — this also matches "JSmith@Acme.com"
$event = AuditLog::whereEncryptedStringExact('actor_name', 'john smith')->first();
```

This is the encrypted-column equivalent of:

```sql
SELECT * FROM audit_logs WHERE LOWER(actor_email) = LOWER('jsmith@acme.com');
```

#### Partial String Match

`whereEncryptedStringPartial()` returns records where the term appears anywhere in the decrypted value (case-insensitive substring).

```php
// Every actor whose email is on the acme.com domain
$events = AuditLog::whereEncryptedStringPartial('actor_email', 'acme.com')->get();

// Every event whose old value contained "pending"
$events = AuditLog::whereEncryptedStringPartial('attribute_value_old', 'pending')->get();
```

#### Starts-With and Ends-With

`whereEncryptedStringStartsWith()` and `whereEncryptedStringEndsWith()` anchor the match to the beginning or end of the value.

```php
// Emails that start with "jsmith"
$events = AuditLog::whereEncryptedStringStartsWith('actor_email', 'jsmith')->get();

// Emails that end with the acme.com domain
$events = AuditLog::whereEncryptedStringEndsWith('actor_email', 'acme.com')->get();
```

> Matching is **case-insensitive**, like the other scopes. See [Case Sensitivity and Normalization](#case-sensitivity-and-normalization).

#### Array Column Searches

The `metadata` column is an encrypted array (`encrypted:array`). These scopes search inside it. The examples assume rows created with metadata like:

```php
AuditLog::create(
    // ...
    database: true,
    metadata: [
        'approved_by' => 'John Smith',
        'ticket' => 'CHG-10482',
    ],
);
```

#### Search the Entire Array

`whereEncryptedArraySearch()` performs a case-insensitive substring search across **all keys and values** of the array.

```php
// Any metadata that mentions "acme" in any key or value
$events = AuditLog::whereEncryptedArraySearch('metadata', 'acme')->get();
```

> `whereEncryptedArraySearch()` always reads live data and does not take a `cache` argument.

#### Match a Specific Array Key

The keyed scopes target one array key:

```php
// Exact (case-insensitive) match on metadata['approved_by']
$events = AuditLog::whereEncryptedArrayExact('metadata', 'approved_by', 'John Smith')->get();

// Partial match on metadata['approved_by']
$events = AuditLog::whereEncryptedArrayPartial('metadata', 'approved_by', 'john')->get();

// Prefix / suffix match on metadata['ticket']
$events = AuditLog::whereEncryptedArrayStartsWith('metadata', 'ticket', 'chg-')->get();
$events = AuditLog::whereEncryptedArrayEndsWith('metadata', 'ticket', '10482')->get();
```

#### Listing Array Keys

`encryptedArrayKeys()` (a static method, not a scope) returns the distinct set of keys present across an encrypted array column — useful for building filters or discovering what has been stored.

```php
$keys = AuditLog::encryptedArrayKeys('metadata');

// [
//     0 => 'approved_by',
//     1 => 'ticket',
//     2 => 'source_ip',
// ]
```

#### Chaining with Other Constraints

Because the scopes return a query builder, combine them with ordinary constraints, ordering, and pagination:

```php
// Failed login events for a given user, newest first, paginated
$events = AuditLog::whereEncryptedStringExact('actor_email', 'jsmith@acme.com')
    ->where('event_type', 'okta.auth.login.error.invalid')
    ->orderByDesc('occurred_at')
    ->paginate(25);

// Count how many events referenced a value
$count = AuditLog::whereEncryptedStringPartial('record_reference_value', 'acme corp')->count();

// Include soft-deleted rows in the results
$events = AuditLog::withTrashed()
    ->whereEncryptedStringExact('actor_username', 'jsmith')
    ->get();
```

> The lookup index is built including soft-deleted rows, but the returned query still applies the model's soft-delete scope. Chain `->withTrashed()` (as above) if you want trashed records in the results.

#### Case Sensitivity and Normalization

All of the scopes are **case-insensitive**. The lookup index normalizes every stored value to lowercase, and each scope lowercases your search term before comparing, so `John Smith`, `john smith`, and `JOHN SMITH` all match the same records.

#### Caching and Fresh Data

Building the lookup index decrypts every row, so the result is cached (the cache payload is itself encrypted) for two minutes. Repeated searches within that window are served from cache.

Pass `cache: false` as the final argument to bypass and rebuild the index — use this immediately after writing rows you need to search:

```php
// Force a fresh index (e.g., right after creating new audit rows)
$events = AuditLog::whereEncryptedStringExact('actor_email', 'jsmith@acme.com', cache: false)->get();

$events = AuditLog::whereEncryptedArrayExact('metadata', 'approved_by', 'John Smith', cache: false)->get();
```

#### Performance Considerations

These scopes trade database-side filtering for the ability to search encrypted data:

- On a cache miss, the entire column is decrypted in memory to build the `id => value` index, so cost grows with table size. The index is built over the whole table regardless of any constraints you chain afterward (those constrain the returned builder, not the index). This suits moderate tables and background jobs.
- Prefer non-encrypted, indexed columns (`event_type`, `record_type` / `record_id`, `actor_id`, `occurred_at`) for high-volume filtering, and use encrypted search to locate specific actors or values.

### Date Range Scopes

The base model ships query scopes for filtering persisted records by date. Each accepts any [Carbon](https://carbon.nesbot.com/)-parsable date or datetime string (or a `Carbon`/`DateTime` instance), and the comparison is **inclusive** (`<=` / `>=`). Throughout this section, `AuditLog` refers to **your Eloquent model** — either the base `BoldlyGrow\AuditLog\Models\AuditLog` or your own `App\Models\AuditLog` that extends it.

| Scope | Column | Description |
|-------|--------|-------------|
| `createdBefore($date)` | `created_at` | Records written on or before the date |
| `createdAfter($date)` | `created_at` | Records written on or after the date |
| `occurredBefore($date)` | `occurred_at` | Records whose event `occurred_at` is on or before the date |
| `occurredAfter($date)` | `occurred_at` | Records whose event `occurred_at` is on or after the date |
| `deletedBefore($date)` | `deleted_at` | **Soft-deleted** records deleted on or before the date |
| `deletedAfter($date)` | `deleted_at` | **Soft-deleted** records deleted on or after the date |

Every scope returns an Eloquent query builder, so it chains with additional `->where()` constraints, ordering, and pagination just like a normal query:

```php
use App\Models\AuditLog;

// Records written within a single day
$events = AuditLog::createdAfter('2026-07-01')
    ->createdBefore('2026-07-01 23:59:59')
    ->get();

// Events that occurred in the last week, most recent first
$events = AuditLog::occurredAfter(now()->subWeek())
    ->orderByDesc('occurred_at')
    ->paginate(25);

// Combine with any other constraint
$events = AuditLog::createdAfter('2026-01-01')
    ->where('event_type', 'okta.auth.login.error.invalid')
    ->count();
```

The `created_at` scopes always apply. `occurred_at` is only populated when you pass `occurred_at` to `AuditLog::create()`, so records logged without it are excluded from the `occurred*` scopes. The `deleted*` scopes call `onlyTrashed()` internally — they return **only** soft-deleted records, so there is no need to add `withTrashed()` yourself.

### Querying by Relationship

Each audit log carries six polymorphic relationships — `actor`, `parent`, `record`, `related`, `subject`, and `tenant` — that morph on the fully-qualified class name stored in the paired `*_model` column (the `actor` relationship uses `actor_type`, which also stores the FQCN). Because the model registers these as standard `MorphTo` relationships and requires no morph map, you can filter by them with Laravel's **native** query builder methods — no package-specific scopes needed. Throughout this section, `AuditLog` refers to **your Eloquent model** — either the base `BoldlyGrow\AuditLog\Models\AuditLog` or your own `App\Models\AuditLog` that extends it.

#### Matching a Related Model with `whereMorphedTo()`

[`whereMorphedTo()`](https://laravel.com/docs/eloquent-relationships#querying-morph-to-relationships) takes a relationship name plus either a **model instance** or a **class-string**, and resolves the underlying columns for you:

```php
use App\Models\AuditLog;
use App\Models\Okta\User;

// Logs for ONE specific record — matches record_model = User::class AND record_id = $user->id
$events = AuditLog::whereMorphedTo('record', $user)->get();

// Logs for ANY record of a class — matches record_model = 'App\Models\Okta\User' (any id)
$events = AuditLog::whereMorphedTo('record', User::class)->get();
```

The same call works for every relationship — just swap the first argument:

```php
$events = AuditLog::whereMorphedTo('actor', $user)->get();     // who performed the action
$events = AuditLog::whereMorphedTo('subject', $user)->get();   // who/what was impacted
$events = AuditLog::whereMorphedTo('parent', $model)->get();   // the parent record
$events = AuditLog::whereMorphedTo('record', $model)->get();   // the affected record
$events = AuditLog::whereMorphedTo('related', $model)->get();  // a related record
$events = AuditLog::whereMorphedTo('tenant', $org)->get();     // the owning organization/tenant
```

What you pass determines how the query is constrained:

| Argument | Constrains | Meaning |
|----------|------------|---------|
| A model instance (`$user`) | `*_model` **and** `*_id` | Records tied to this exact model |
| A class-string (`User::class`) | `*_model` only | Records tied to any instance of this class |

Because every scope returns a query builder, these compose with the [date range scopes](#date-range-scopes) and any other constraint, ordering, or pagination:

```php
// Everything this actor did in the last 7 days, most recent first
$events = AuditLog::whereMorphedTo('actor', $user)
    ->occurredAfter(now()->subDays(7))
    ->orderByDesc('occurred_at')
    ->get();

// Actions against a specific tenant since the start of the year, paginated
$events = AuditLog::whereMorphedTo('tenant', $organization)
    ->createdAfter('2026-01-01')
    ->paginate(25);
```

`whereNotMorphedTo()` is available for the inverse.

#### Filtering by the Related Model's Attributes with `whereHasMorph()`

When you need a condition on the related record itself — not just its identity — use [`whereHasMorph()`](https://laravel.com/docs/eloquent-relationships#querying-relationship-existence). Pass the type(s) to check as the second argument, since a `MorphTo` may point at several classes:

```php
use App\Models\Okta\User;

// Logs whose subject is an active user
$events = AuditLog::whereHasMorph('subject', [User::class], function ($query) {
    $query->where('is_active', true);
})->get();
```

### Example API Endpoints

The package ships copyable reference implementations of a read-only `list` + `describe` audit log API in the [`examples/`](examples) directory — in two flavors:

- [`api-endpoints-no-dependency`](examples/api-endpoints-no-dependency) — a primitive listing (the model results, paginated) using pure Laravel/Eloquent; no extra packages.
- [`api-endpoints-spatie-data-query`](examples/api-endpoints-spatie-data-query) — declarative `filter[...]` query parameters (including encrypted-field search) and a typed response DTO, using `spatie/laravel-query-builder` and `spatie/laravel-data`.

The Spatie variant exposes the `*_type` strings described above as query filters. They are reference stubs (not autoloaded) — copy them into your app and adapt; each folder's README covers install, setup, and authorization.

### Adding Custom Fields

You will often need to store application-specific fields (for example a tenant, organization, or workspace ID) on your audit log records. There are two approaches.

#### Recommended: Metadata Flatten (no package changes)

1. Add the column(s) to the table with your own migration:

   ```php
   Schema::table('audit_logs', function (Blueprint $table) {
       $table->string('federation_organization_id')->nullable()->index();
   });
   ```

2. Whitelist the key in `config('audit-log.database.custom_fields')`:

   ```php
   'database' => [
       'custom_fields' => [
           'federation_organization_id',
       ],
   ],
   ```

3. Add a relationship to your own `App\Models\AuditLog` model (if you created one extending the base model) if desired.

4. Pass the value inside `metadata`:

   ```php
   AuditLog::create(
       // ...
       database: true,
       metadata: [
           'federation_organization_id' => $organization->id,
       ],
       // ...
   );
   ```

Whitelisted keys are flattened out of `metadata` into their matching columns. They also remain in the `metadata` JSON for the system log.

#### Advanced: First-Class Parameter

If you want a custom field as a first-class parameter of `AuditLog::create()` (rather than passed via `metadata`), the value must be added in each of these places within the package:

- The `create()` method signature and docblock
- The `$log_context_keys` grouping
- The `validate()` rules
- The `$log_data` array (for persistence)
- The base model `casts()` (if a non-string cast is needed)
- The migration

### Skipping Log Creation

You can specify true/false booleans for the `log` and `database` parameters. By default, `log` is `true` and `database` is `false`. (`transaction` is retained as a deprecated alias for `database`.)

The parsed and formatted schema is always returned as an array.

| `log`   | `database`    | Behavior                                                            |
|---------|---------------|--------------------------------------------------------------------|
| `true`  | `false`       | (Default) Log entry is created.                                    |
| `true`  | `true`        | Log entry is created. Database row is persisted.                   |
| `false` | `true`        | No log is created. Database row is persisted.                      |
| `false` | `false`       | No log or database row is created. Used for schema parsing.        |

## Response Schema

One of the benefits to this package is the formatted array with predictable keys.

In addition to (or instead of) writing a log or persisting to the database, every call returns a formatted array with all of the keys and their provided or default values. This is useful if you simply need an array that you will use for your own changelog or some other purpose. You can also disable log creation and use this like a data transfer object (DTO).

Simply define a variable to get the returned array.

```php
use BoldlyGrow\AuditLog\AuditLog;

// Create a log entry with no returned array
AuditLog::create(
    // ...
    log: true
    // ...
);

// Define a variable with the returned array
$schema = AuditLog::create(
    // ...
    log: true
    // ...
);

// Append the return array to an existing array of records that are not created in system logs
foreach($records as $record) {
    // ...

    $changelog[] = AuditLog::create(
        // ...
        log: false
        // ...
    );
}
```

### Example Response Array with No Configuration

```php
use BoldlyGrow\AuditLog\AuditLog;

$event_ms = now();

$result = AuditLog::create(
    event_ms: $event_ms,
    event_type: "okta.api.post.success.ok",
    level: "info",
    message: "Success",
    metadata: [
        "okta_request_id" => "REDACTED",
        "rate_limit_remaining" => "199",
        "uri" => "users",
        "url" => "https://dev-12345678.okta.com/api/v1/users?activate=true"
    ],
    method: "BoldlyGrow\Okta\ApiClient::get"
);

dd($result);

// [
//     "datetime" => "2024-03-02T18:51:10+00:00",
//     "event_type" => "okta.api.post.success.ok",
//     "level" => "info",
//     "message" => "Success",
//     "method" => "BoldlyGrow\Okta\ApiClient::get",
//     "actor_email" => null,
//     "actor_id" => null,
//     "actor_ip_addr" => null,
//     "actor_name" => null,
//     "actor_provider_id" => null,
//     "actor_session_id" => "…",
//     "actor_source" => "system",
//     "actor_type" => null,
//     "actor_username" => null,
//     "attribute_key" => null,
//     "attribute_value_old" => null,
//     "attribute_value_new" => null,
//     "count_records" => null,
//     "duration_ms" => null,
//     "duration_ms_per_record" => null,
//     "errors" => [],
//     "event_ms" => 4,
//     "event_ms_per_record" => null,
//     "job_batch" => null,
//     "job_id" => null,
//     "job_platform" => null,
//     "job_pipeline_id" => null,
//     "job_timestamp" => null,
//     "job_transaction_id" => null,
//     "metadata" => [
//       "okta_request_id" => "REDACTED",
//       "rate_limit_remaining" => "199",
//       "uri" => "users",
//       "url" => "https://dev-12345678.okta.com/api/v1/users?activate=true",
//     ],
//     "occurred_at" => null,
//     "parent_id" => null,
//     "parent_type" => null,
//     "parent_provider_id" => null,
//     "parent_reference_key" => null,
//     "parent_reference_value" => null,
//     "record_id" => null,
//     "record_type" => null,
//     "record_provider_id" => null,
//     "record_reference_key" => null,
//     "record_reference_value" => null,
//     "related_id" => null,
//     "related_type" => null,
//     "subject_id" => null,
//     "subject_type" => null,
//     "tenant_id" => null,
//     "tenant_type" => null,
// ]
```

> The `*_model` parameters do not appear as separate keys in the returned array — they are folded into their paired `*_type` value. When a database row is persisted, both the `*_model` (fully-qualified class) and `*_type` (snake_case) columns are stored.

### Specifying Keys in Response Array

There are a large number of parameter keys in the schema. To avoid having to use Laravel Collections transform methods with the result array, you can simply pass an array of keys to the `dump_keys` array that you want to be included.

```php
use BoldlyGrow\AuditLog\AuditLog;

$result = AuditLog::create(
    // ...
    dump_keys: [
        'event_type',
        'message',
        'attribute_key',
        'attribute_value_old',
        'attribute_value_new',
        'record_id',
        'record_type',
        'record_provider_id',
        'record_reference_key',
        'record_reference_value'
    ],
);

dd($result);

// [
//     "datetime" => "2024-03-02T18:53:35+00:00",
//     "event_type" => "okta.api.post.success.ok",
//     "message" => "Success",
//     "attribute_key" => null,
//     "attribute_value_old" => null,
//     "attribute_value_new" => null,
//     "record_id" => null,
//     "record_type" => null,
//     "record_provider_id" => null,
//     "record_reference_key" => null,
//     "record_reference_value" => null,
// ]
```

### Specifying Custom Static Strings in Response Array

If you need to add custom static strings to your array, they can be specified in the `dump_strings` array. Strings are returned at the end of the array.

```php
use BoldlyGrow\AuditLog\AuditLog;

$result = AuditLog::create(
    // ...
    dump_keys: [
        'event_type',
        'message',
        'attribute_key',
        'attribute_value_old',
        'attribute_value_new',
        'record_id',
        'record_type',
        'record_provider_id',
        'record_reference_key',
        'record_reference_value'
    ],
    dump_strings: [
        'custom_key' => 'my_value',
        'another_key' => 'my_value',
    ],
    // ...
);

dd($result);

// [
//     "datetime" => "2024-03-02T18:54:55+00:00",
//     "event_type" => "okta.api.post.success.ok",
//     "message" => "Success",
//     "attribute_key" => null,
//     "attribute_value_old" => null,
//     "attribute_value_new" => null,
//     "record_id" => null,
//     "record_type" => null,
//     "record_provider_id" => null,
//     "record_reference_key" => null,
//     "record_reference_value" => null,
//     "custom_key" => "my_value",
//     "another_key" => "my_value",
// ]
```

#### Custom Datetime Format in Response Array

The `datetime` is always returned in the first key (table column) of the array. You can customize the format using a string of supported [PHP datetime format characters](https://www.php.net/manual/en/datetime.format.php). By default, we use `c` for the ISO 8601 format (YYYY-MM-DDTHH:II:SS+00:00). You may want to simplify this with `Y-m-d` or `Y-m-d H:i`.

```php
use BoldlyGrow\AuditLog\AuditLog;

$result = AuditLog::create(
    // ...
    dump_date: 'c',
    dump_keys: [
        'event_type',
        'message',
        'attribute_key',
        'attribute_value_old',
        'attribute_value_new',
        'record_id',
        'record_type',
        'record_provider_id',
        'record_reference_key',
        'record_reference_value'
    ],
    dump_strings: [
        'custom_key' => 'my_value',
        'another_key' => 'my_value',
    ],
    // ...
);

dd($result);

// [
//     "datetime" => "2024-03-02",
//     "event_type" => "okta.api.post.success.ok",
//     // ....
// ]
```

### Standardized Configurations for Response Array

> Reminder: You need to [publish the configuration file](#publish-the-config-and-migration) for it to appear in `config/audit-log.php` or it will use the default one in the `vendor/boldlygrow/audit-log` directory that cannot be modified.

It can be difficult to manage your simplified schemas throughout your code base.

You can define standardized simplified schemas in the `config/audit-log.php` file for each of your use cases. The `default` key is a placeholder that can be customized and you can add additional arrays for each type of resource if needed (ex. `okta_user`).

After a schema is defined, simply set the `dump_config` key to the same key that was defined in `config/audit-log.php`.

```php
use BoldlyGrow\AuditLog\AuditLog;

$result = AuditLog::create(
    // ...
    dump_config: 'okta_user'
    // ...
);
```

```php
// config/audit-log.php

return [
    'dump' => [
        'default' => [
            'date' => 'c',
            'strings' => [
                'custom_key' => 'my_value'
            ],
            'keys' => [
                'event_type',
                'message',
                'record_id',
                'record_type',
                'record_provider_id',
                'record_reference_key',
                'record_reference_value'
            ],
        ],
        'okta_user' => [
            'date' => 'c',
            'strings' => [
                'custom_key' => 'my_value'
            ],
            'keys' => [
                'event_type',
                'message',
                'attribute_key',
                'attribute_value_old',
                'attribute_value_new',
                'record_id',
                'record_type',
                'record_provider_id',
                'record_reference_key',
                'record_reference_value'
            ]
        ]
    ]
];
```

### Real World Example for Response Arrays

```php
use BoldlyGrow\AuditLog\AuditLog;

$result = AuditLog::create(
    attribute_key: $attribute,
    attribute_value_old: $manifest_record[$attribute],
    attribute_value_new: $api_record[$attribute],
    duration_ms: $this->duration_ms,
    event_type: $this->event_type . '.datadumper.manifest.attribute.changed.' . $attribute,
    level: 'info',
    log: true,
    message: Str::title($attribute) . ' Attribute Value Changed',
    method: __METHOD__,
    record_provider_id: $manifest_record['provider_id'],
    record_reference_key: $this->reference_key,
    record_reference_value: $manifest_record[$this->reference_key] ?? null,
    database: true
);

dd($result);

// [
//     'datetime' => 'YYYY-MM-DDTHH:II:SS+00:00',
//     'event_type' => 'okta.user.sync.datadumper.manifest.attribute.changed.manager',
//     'message' => 'Manager Attribute Value Changed',
//     'attribute_key' => 'manager',
//     'attribute_value_old' => 'klibby',
//     'attribute_value_new' => 'dmurphy',
//     'record_id' => null,
//     'record_type' => 'okta_user',
//     'record_provider_id' => '00u1b2c3d4e5f6g7h8i9',
//     'record_reference_key' => 'handle',
//     'record_reference_value' => 'jpardella'
//     'custom_key' => 'my_value',
// ]
```
