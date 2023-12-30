# Audit and Event Log Handler

[[_TOC_]]

## Overview

The Audit package is an open source [Composer](https://getcomposer.org/) package for use in Laravel applications that is used by other [Provisionesta](https://gitlab.com/provisionesta) packages to provide a consistent log syntax and add support for database transactions (upcoming release) for time sensitive events. Although this is purpose built for our packages, you are welcome to adopt this for your own standardized logging.

This is maintained by the open source community and is not maintained by any company. Please use at your own risk and create merge requests for any bugs that you encounter.

### Problem Statement

When using [Laravel Logging](https://laravel.com/docs/10.x/logging) with the `Log::info('Message', ['key1' => 'value', 'key2' => 'value'])` syntax, it is easy to have inconsistency with log formatting that results in a variety of log messages and varying context keys.

The `Provisionesta\Audit\Log::create()` method provides a pre-defined set of context keys that allow us to improve indexing and searchability in external logging platforms, and ensures that all events provide as much context data as possible in a consistent format.

(Upcoming release) Since some log events need to be actioned, this package adds support for an `audit_transactions` database table that allows you to centrally manage it like a background job queue and trigger different actions and workflows based on your own application and business requirements.

### Example Usage

```php
use Provisionesta\Audit\Log;

Log::create(
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

### Example Output

```plain
[2023-12-01 08:01:23] local.DEBUG: ApiClient::post Success {"event_type":"okta.api.post.success.ok","method":"Provisionesta\\Okta\\ApiClient::post","event_ms":627,"metadata":{"okta_request_id":"REDACTED","rate_limit_remaining":"16","uri":"users","url":"https://dev-12345678.okta.com/api/v1/users?activate=true"}"}
```

```json
{
    "event_type": "okta.api.post.success.ok",
    "method": "Provisionesta\\Okta\\ApiClient::post",
    "event_ms": 627,
    "metadata": {
        "okta_request_id": "REDACTED",
        "rate_limit_remaining": "16",
        "uri": "users",
        "url": "https://dev-12345678.okta.com/api/v1/users?activate=true"
    }
}
```


### Issue Tracking and Bug Reports

We do not maintain a roadmap of feature requests, however we invite you to contribute and we will gladly review your merge requests.

Please create an [issue](https://gitlab.com/provisionesta/okta-api-client/-/issues) for bug reports.

### Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) to learn more about how to contribute.

### Maintainers

| Name | GitLab Handle | Email |
|------|---------------|-------|
| [Jeff Martin](https://www.linkedin.com/in/jeffersonmmartin/) | [@jeffersonmartin](https://gitlab.com/jeffersonmartin) | `provisionesta [at] jeffersonmartin [dot] com` |

### Contributor Credit

- [Jeff Martin](https://gitlab.com/jeffersonmartin)

## Installation

### Requirements

| Requirement | Version                          |
|-------------|----------------------------------|
| PHP         | `^8.0`, `^8.1`, `^8.2`, `^8.3`   |
| Laravel     | `^8.0`, `^9.0`, `^10.0`, `^11.0` |

### Upgrade Guide

See the [changelog](https://gitlab.com/provisionesta/okta-api-client/-/blob/main/changelog/) for release notes.

### Add Composer Package

```plain
composer require provisionesta/audit:^1.0
```

If you are contributing to this package, see [CONTRIBUTING.md](CONTRIBUTING.md) for instructions on configuring a local composer package with symlinks.

## Usage

### Create a Log Entry

You can copy and paste this example anywhere in your code that you would create a log entry. Any arguments that are not relevant can be removed and will be considered null.

```php
use Provisionesta\Audit\Log;

Log::create(
    attribute_key: 'xxx',
    attribute_value_old: 'xxx',
    attribute_value_new: 'xxx',
    count_records: count($array),
    duration_ms: $duration_ms,
    duration_ms_per_record: (int) ($duration_ms / count($records)),
    errors: [],
    event_ms: $event_ms,
    event_ms_per_record: (int) ($event_ms / count($records)),
    event_type: '{provider}.{entity}.{action}.xxx',
    level: 'info',
    message: '{What happened}',
    metadata: [],
    method: __METHOD__,
    occurred_at: $entity->created_at,
    parent_id: $parent->id,
    parent_type: 'App\\Models\\{Provider}\\Application',
    parent_reference_key: 'name',
    parent_reference_value: $entity->organization->name,
    record_id: $entity->id,
    record_type: 'App\\Models\\{Provider}\\{Entity}',
    record_provider_id: $entity->provider_id,
    record_reference_key: 'name',
    record_reference_value: $entity->name,
    tenant_id: $entity->provider_organization_id,
    tenant_type: 'App\\Models\\{Provider}\\Organization',
    transaction: false
);
```

### Log Parameter Definitions

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
<td>transaction<br /><code>bool</code></td>
<td><code>true</code><br /><code>false</code></td>
<td>Whether to create a Transaction database entry for this event</td>
</tr>
</tbody>
</table>
