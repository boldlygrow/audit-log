# Audit and Event Log Handler

[[_TOC_]]

## Overview

The Audit package is an open source [Composer](https://getcomposer.org/) package for use in Laravel applications that is used by other [Provisionesta](https://gitlab.com/provisionesta) packages to provide a consistent log syntax and add support for database transactions (upcoming release) for time sensitive events. Although this is purpose built for our packages, you are welcome to adopt this for your own standardized logging.

This is maintained by the open source community and is not maintained by any company. Please use at your own risk and create merge requests for any bugs that you encounter.

### Problem Statement

When using [Laravel Logging](https://laravel.com/docs/10.x/logging) with the `Log::info('Message', ['key1' => 'value', 'key2' => 'value'])` syntax, it is easy to have inconsistency with log formatting that results in a variety of log messages and varying context keys.

The `Provisionesta\Audit\Log::create()` method provides a pre-defined set of context keys that allow us to improve indexing and searchability in external logging platforms, and ensures that all events provide as much context data as possible in a consistent format.

Sometimes you need to get a formatted array that can be added to a changelog or actioned upon programmatically instead of trying to tail a log file. An array is returned for each log entry that is created.

(Upcoming release) Since some log events need to be actioned, this package adds support for an `audit_transactions` database table that allows you to centrally manage it like a background job queue and trigger different actions and workflows based on your own application and business requirements.

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
| PHP         | `^8.0`                           |
| Laravel     | `^8.0`, `^9.0`, `^10.0`, `^11.0` |

### Upgrade Guide

See the [changelog](https://gitlab.com/provisionesta/audit/-/blob/main/changelog/) for release notes.

### Add Composer Package

```plain
composer require provisionesta/audit:^1.1
```

If you are contributing to this package, see [CONTRIBUTING.md](CONTRIBUTING.md) for instructions on configuring a local composer package with symlinks.

### Publish the configuration file

**This is optional**. The configuration file the custom schemas if you are returning the parsed log entry as a variable.

```plain
php artisan vendor:publish --tag=audit
```

## Usage Examples

### Basic Usage

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

### Comprehensive Usage

You can copy and paste this example anywhere in your code that you would create a log entry. Any arguments that are not relevant can be removed and will be considered null.

```php
use Provisionesta\Audit\Log;

Log::create(
    attribute_key: 'xxx',
    attribute_value_old: 'xxx',
    attribute_value_new: 'xxx',
    count_records: count($array),
    dump: false,
    dump_keys: [],
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

### Example Output

```plain
[YYYY-MM-DD HH:II:SS] local.DEBUG: ApiClient::post Success {"event_type":"okta.api.post.success.ok","method":"Provisionesta\\Okta\\ApiClient::post","event_ms":627,"metadata":{"okta_request_id":"REDACTED","rate_limit_remaining":"16","uri":"users","url":"https://dev-12345678.okta.com/api/v1/users?activate=true"}"}
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
<td>dump_config<br /><code>string</code></td>
<td><code>default</code></td>
<td>(<a href="#response-schema">Response Schema</a>) The array key in <code>config/audit.php</code> that contains the <code>date</code>, <code>keys</code>, <code>strings</code> <a href="#standardized-configurations-for-response-array">schema configuration</a>. The other <code>dump_*</code> parameters are ignored if <code>dump_config</code> is set.</td>
</tr>
<tr>
<td>dump_date<br /><code>string</code></td>
<td><code>c</code><br><code>Y-m-d</code><br><code>Y-m-d H:i:s</code></td>
<td>(<a href="#response-schema">Response Schema</a>) The <a target="_blank" href="https://www.php.net/manual/en/datetime.format.php">PHP datetime format</a> string for timestamps returned in the response array. </td>
</tr>
<tr>
<td>dump_keys<br /><code>array</code></td>
<td>See <a href="#response-schema">docs</a></td>
<td>(<a href="#response-schema">Response Schema</a>) An filtered list of array of keys from the <code>Log::create()</code> method that returned in the response array.</td>
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
<td><code>true</code><br /><code>false</code> (default)</td>
<td>Whether to create a Transaction database entry for this event</td>
</tr>
</tbody>
</table>

## Advanced Usage

### Background Job Log Entry

You can add the `job_*` parameters if you are running background jobs and want to add metadata to your logs and transactions. All of these values (except `job_timestamp`) are freeform strings that you can standardize however you'd like.

```php
use Provisionesta\Audit\Log;

Log::create(
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

### Skipping Log Creation

You can specify true/false booleans for `log`, and `transaction` parameters. By default, `log` is `true` and `transaction` is `false`.

The parsed and formatted schema is always returned as an array.

| `log`   | `transaction` | Behavior                                                                |
|---------|---------------|-------------------------------------------------------------------------|
| `true`  | `false`       | (Default) Log entry is created.                                         |
| `true`  | `true`        | Log entry is created. Database row is created for transaction.          |
| `false` | `true`        | No log is created. Database row is created for transaction.             |
| `false` | `false`       | No log or transaction database row is created. Used for schema parsing. |

## Response Schema

One of the benefits to this package is the formatted array with predictable keys.

As an alternative to creating a transaction, you can also return a formatted array with all of the keys with provided values (or their default values). This is useful if you simply need an array that you will use for your own changelog or some other purpose. You can also disable log creation and use this like a data transfer object (DTO).

Simply define a variable to get the returned array.

```php
use Provisionesta\Audit\Log;

// Create a log entry with no returned array
Log::create(
    // ...
    log: true
    // ...
);

// Define a variable with the returned array
$schema = Log::create(
    // ...
    log: true
    // ...
);

// Append the return array to an existing array of records that are not created in system logs
foreach($records as $record) {
    // ...

    $changelog[] = Log::create(
        // ...
        log: false
        // ...
    );
}
```

### Example Response Array with No Configuration

```php
use Provisionesta\Audit\Log;

$event_ms = now();

$result = Log::create(
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
    method: "Provisionesta\Okta\ApiClient::get"
);

dd($result);

// [
//     "datetime" => "2024-03-02T18:51:10+00:00",
//     "event_type" => "okta.api.post.success.ok",
//     "level" => "info",
//     "message" => "Success",
//     "method" => "Provisionesta\Okta\ApiClient::get",
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
//     "tenant_id" => null,
//     "tenant_type" => null,
// ]
```

### Specifying Keys in Response Array

There are a large number of parameter keys in the schema. To avoid having to use Laravel Collections transform methods with the result array, you can simply pass an array of keys to the `dump_keys` array that you want to be included.

```php
use Provisionesta\Audit\Log;

$result = Log::create(
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
use Provisionesta\Audit\Log;

$result = Log::create(
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
use Provisionesta\Audit\Log;

$result = Log::create(
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

> Reminder: You need to [publish the configuration file](#publish-the-configuration-file) for it to appear in `config/audit.php` or it will use the default one in the `vendor/provisionesta/audit` directory that cannot be modified.

It can be difficult to manage your simplified schemas throughout your code base.

You can define standardized simplified schemas in the `config/audit.php` file for each of your use cases. The `default` key is a placeholder that can be customized and you can add additional arrays for each type of resource if needed (ex. `okta_user`).

After a schema is defined, simply set the `dump_config` key to the same key that was defined in `config/audit.php`.

```php
use Provisionesta\Audit\Log;

$result = Log::create(
    // ...
    dump_config: 'okta_user'
    // ...
);
```

```php
// config/audit.php

return [
    'dump' => [
        'default' => [
            'date' => 'c'
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
use Provisionesta\Audit\Log;

$result = Log::create(
    attribute_key: $attribute,
    attribute_value_old: $manifest_record[$attribute],
    attribute_value_new: $api_record[$attribute],
    duration_ms: $this->duration_ms,
    event_type: $this->event_type . '.datadumper.manifest.attribute.changed.' . $attribute,
    level: 'info',
    log: true
    message: Str::title($attribute) . ' Attribute Value Changed',
    method: __METHOD__,
    record_provider_id: $manifest_record['provider_id'],
    record_reference_key: $this->reference_key,
    record_reference_value: $manifest_record[$this->reference_key] ?? null,
    transaction: true
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
