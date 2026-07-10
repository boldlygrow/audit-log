<?php

/**
 * @copyright Jefferson Martin
 * @license MIT <https://spdx.org/licenses/MIT.html>
 * @link https://github.com/boldlygrow/audit-log
 */

namespace BoldlyGrow\AuditLog;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log as LaravelLog;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use BoldlyGrow\AuditLog\Exceptions\ValidationException;

class AuditLog
{
    /**
     * Create Audit Log and Dispatch Transaction
     *
     * @link https://docs.provisionr.app/architecture/audit/log/create
     *
     * @param string $event_type
     *      The octet notation event type that follows our codestyle conventions.
     *      Ex. `{provider}.{entity}.{action}.{result}.{reason?}`
     *
     * @param string $level
     *      The log level for the log entry
     *      Ex. debug|info|notice|warning|error|critical|alert|emergency
     *
     * @param string $message
     *      A short message to include in the logs. This will be auto-prefixed
     *      with the fully-qualified method name (the "noun") so you can keep
     *      the message focused on the "verb" language.
     *      Ex. validation failed
     *
     * @param string $method
     *      The method where this audit log is created in or is on behalf of.
     *      Ex. __METHOD__
     *
     * @param ?string $actor_email (optional)
     *      The email address of the actor
     *      Ex. auth()->user()->email
     *
     * @param ?string $actor_id (optional)
     *      The database ID of the actor
     *      Ex. auth()->user()->id
     *
     * @param ?string $actor_ip_addr (optional)
     *      The IP address of the actor
     *      Ex. request()->ip()
     *
     * @param ?string $actor_name (optional)
     *      The first and last name of the actor
     *      Ex. auth()->user()->name
     *
     * @param ?string $actor_provider_id (optional)
     *      The 3rd party vendor API ID of the actor (ex. Okta User ID)
     *      Ex. auth()->user()->provider_id
     *
     * @param ?string $actor_session_id (optional)
     *      The session ID of the actor
     *      Ex. session()->getId()
     *
     * @param ?string $actor_source (optional)
     *      The origin of the request that generated this event. When null and
     *      enabled, it is auto-detected as `system` (console), `api` (API route
     *      or JSON request), or `web`. Must be one of the values configured in
     *      `config('audit-log.actor.source.allowed')`.
     *
     * @param ?string $actor_type (optional)
     *      (Many-to-Many Relationship Events) The fully-qualified namespace of
     *      the database model with a many-to-many relationship.
     *      Ex. App\\Models\\Auth\\User
     *      Ex. config('auth.providers.users.model')
     *
     * @param ?string $actor_username (optional)
     *      The username of the actor
     *      Ex. auth()->user()->username
     *
     * @param ?string $attribute_key (optional)
     *      (State Changes) The database column name that has changed.
     *
     * @param ?string $attribute_value_old (optional)
     *      (State Changes) The value in the database before the update.
     *
     * @param ?string $attribute_value_new (optional)
     *      (State Changes) The API value that is now updated in the database.
     *
     * @param ?int $count_records (optional)
     *      (Multiple records) Count of records processed.
     *
     * @param bool $database (optional)
     *      Whether to persist this event to the database using the model in
     *      `config('audit-log.database.model')`. Requires
     *      `config('audit-log.database.enabled')` to be true.
     *      (default: false)
     *
     * @param ?string $dump_config (optional)
     *      The array key in config/audit.php that contains the `date`, `keys`,
     *      and `strings` schema configuration.
     *
     * @param $dump_date (optional)
     *      The PHP datetime format string for timestamps returned in array.
     *      (default: 'c')
     *
     * @param array $dump_keys (optional)
     *      An filtered list of array of keys from the AuditLog::create() method
     *      that returned in the response array.
     *
     * @param array $dump_strings (optional)
     *      An array of key value pairs of static strings that should be
     *      included in the in the response array (instead of having to
     *      add them yourself with collection transformation later).
     *
     * @param ?Carbon $duration_ms (optional)
     *      Carbon instance (timestamp) used for long running batch jobs to
     *      provide a point-in-time duration since job started.
     *
     * @param ?int $duration_ms_per_record (optional)
     *      Number of milliseconds divided by count of records. This is not
     *      auto-calculated to allow flexibility for custom Carbon timestamps
     *
     * @param array $errors (optional)
     *      Flat array of error message(s) that will be encoded as JSON
     *
     * @param ?Carbon $event_ms (optional)
     *      Carbon instance (timestamp) that was initialized at the start of
     *      the action and provides a point-in-time duration for this specific
     *      action within a longer running job.
     *
     * @param ?int $event_ms_per_record (optional)
     *      Number of milliseconds divided by count of records. This is not
     *      auto-calculated to allow flexibility for custom Carbon timestamps
     *
     * @param ?string $job_batch (optional)
     *      The human identifier string or system ID of the batch of jobs.
     *      Format is at your discretion.
     *
     * @param ?string $job_id (optional)
     *      The human identifier string or system ID of the specific job that
     *      triggered this log entry. Format is at your discretion.
     *
     * @param ?string $job_platform (optional)
     *      The human identifier string of the platform that the background
     *      jobs are running in. Format is at your discretion.
     *
     * @param ?string $job_pipeline_id (optional)
     *      The system ID of the CI/CD pipeline (if applicable).
     *
     * @param ?string $job_timestamp (optional)
     *      The timestamp that the job or pipeline was started. This is useful
     *      for identifying which scheduled job timestamp triggered this event.
     *
     * @param ?string $job_transaction_id (optional)
     *      An alternative to job_id that can be used for additional indexable
     *      identifiers used by your application or business logic.
     *
     * @param bool $log (optional)
     *      Whether to create a system log entry for this event. This is used
     *      in conjunction `transaction` or only returning a parsed array.
     *      (default: true)
     *
     * @param array $metadata (optional)
     *      An array of custom metadata that should be included in the log
     *
     * @param ?string $occurred_at (optional)
     *      A datetime that will be formatted with Carbon for when the event
     *      occurred at based on a created_at or updated_at API timestamp
     *
     * @param ?string $parent_id (optional)
     *      (Many-to-Many Relationship Events) The database ID of the database
     *      model with a many-to-many relationship.
     *
     * @param ?string $parent_type (optional)
     *      (Many-to-Many Relationship Events) The fully-qualified namespace of
     *      the database model with a many-to-many relationship.
     *      Ex. App\Models\Okta\Application
     *
     * @param ?string $parent_model (optional)
     *      (Many-to-Many Relationship Events) The fully-qualified model class
     *      name. When provided, `parent_type` is auto-calculated as a snake_case
     *      string and the raw class is persisted to the `parent_model` column.
     *      Ex. \App\Models\Okta\Application::class
     *
     * @param ?string $parent_provider_id (optional)
     *      (Many-to-Many Relationship Events) The API ID of the database model
     *      with a many-to-many relationship that is usually stored in the
     *      database in the `provider_id` column.
     *
     * @param ?string $parent_reference_key (optional)
     *      (Many-to-Many Relationship Events) The database column name for
     *      value that is human readable in logs
     *      Ex. name
     *
     * @param ?string $parent_reference_value (optional)
     *      (Many-to-Many Relationship Events) The value of the human readable
     *      database column
     *
     * @param ?string $record_id (optional)
     *      The database ID of the affected database model
     *
     * @param ?string $record_type (optional)
     *      The fully-qualified namespace of the database model
     *      Ex. App\Models\Okta\User
     *
     * @param ?string $record_model (optional)
     *      The fully-qualified model class name of the affected record. When
     *      provided, `record_type` is auto-calculated as a snake_case string and
     *      the raw class is persisted to the `record_model` column.
     *      Ex. \App\Models\Okta\User::class
     *
     * @param ?string $related_id (optional)
     *      The database ID of a related human or service account.
     *
     * @param ?string $related_model (optional)
     *      The fully-qualified model class name of the related account. When
     *      provided, `related_type` is auto-calculated as a snake_case string.
     *      Ex. \App\Models\Identity::class
     *
     * @param ?string $subject_id (optional)
     *      The database ID of the impacted human or service account subject.
     *
     * @param ?string $subject_model (optional)
     *      The fully-qualified model class name of the subject. Kept generic so
     *      any application module's model may be used. When provided,
     *      `subject_type` is auto-calculated as a snake_case string.
     *      Ex. \App\Models\Service::class
     *
     * @param ?string $record_provider_id (optional)
     *      The API ID of the affected database model that is usually stored in
     *      the database in the `provider_id` column.
     *
     * @param ?string $record_reference_key (optional)
     *      The database column name for value that is human readable in logs
     *      Ex. name
     *
     * @param ?string $record_reference_value (optional)
     *      The value of the human readable database column
     *
     * @param ?string $tenant_id (optional)
     *      The database ID of the top-level organization/tenant for the provider
     *
     * @param ?string $tenant_type (optional)
     *      The fully-qualified namespace of the database model of the top-level
     *      entity (organization, tenant, etc) for the provider.
     *      Ex. App\Models\Okta\Organization
     *
     * @param ?string $tenant_model (optional)
     *      The fully-qualified model class name of the top-level entity. When
     *      provided, `tenant_type` is auto-calculated as a snake_case string.
     *      Ex. \App\Models\Okta\Organization::class
     *
     * @param bool $transaction (optional)
     *      Whether to create a Transaction database entry for this event
     *      (default: false)
     */
    public static function create(
        string $event_type,
        string $level,
        string $message,
        string $method,
        ?string $actor_email = null,
        ?string $actor_id = null,
        ?string $actor_ip_addr = null,
        ?string $actor_name = null,
        ?string $actor_provider_id = null,
        ?string $actor_session_id = null,
        ?string $actor_source = null,
        ?string $actor_type = null,
        ?string $actor_username = null,
        ?string $attribute_key = null,
        ?string $attribute_value_old = null,
        ?string $attribute_value_new = null,
        ?int $count_records = null,
        bool $database = false,
        ?Carbon $duration_ms = null,
        ?string $dump_config = null,
        string $dump_date = 'c',
        array $dump_strings = [],
        array $dump_keys = [],
        ?int $duration_ms_per_record = null,
        array $errors = [],
        ?Carbon $event_ms = null,
        ?int $event_ms_per_record = null,
        ?string $job_batch = null,
        ?string $job_id = null,
        ?string $job_platform = null,
        ?string $job_pipeline_id = null,
        ?string $job_timestamp = null,
        ?string $job_transaction_id = null,
        bool $log = true,
        array $metadata = [],
        ?string $occurred_at = null,
        ?string $parent_id = null,
        ?string $parent_type = null,
        ?string $parent_model = null,
        ?string $parent_provider_id = null,
        ?string $parent_reference_key = null,
        ?string $parent_reference_value = null,
        ?string $record_id = null,
        ?string $record_type = null,
        ?string $record_model = null,
        ?string $record_provider_id = null,
        ?string $record_reference_key = null,
        ?string $record_reference_value = null,
        ?string $related_id = null,
        ?string $related_model = null,
        ?string $subject_id = null,
        ?string $subject_model = null,
        ?string $tenant_id = null,
        ?string $tenant_type = null,
        ?string $tenant_model = null,
        bool $transaction = false,
    ): array {
        self::validate(get_defined_vars());

        $log_context_keys = [
            'string' => [
                'actor_email',
                'actor_id',
                'actor_ip_addr',
                'actor_name',
                'actor_provider_id',
                'actor_session_id',
                'actor_source',
                'actor_type',
                'actor_username',
                'tenant_id',
                'tenant_type',
                'parent_id',
                'parent_type',
                'parent_provider_id',
                'parent_reference_key',
                'parent_reference_value',
                'record_id',
                'record_type',
                'record_provider_id',
                'record_reference_key',
                'record_reference_value',
                'related_id',
                'subject_id',
                'attribute_key',
                'attribute_value_old',
                'attribute_value_new',
                'job_batch',
                'job_id',
                'job_platform',
                'job_pipeline_id',
                'job_timestamp',
                'job_transaction_id'
            ],
            'model_type' => [
                'parent_model',
                'record_model',
                'related_model',
                'subject_model',
                'tenant_model',
            ],
            'int' => [
                'count_records',
                'duration_ms_per_record',
                'event_ms_per_record',
            ],
            'array' => [
                'errors',
                'metadata',
            ],
            'date' => [
                'occurred_at',
            ],
            'ms' => [
                'duration_ms',
                'event_ms',
            ]
        ];

        $actor_ip_addr = self::resolveActorIpAddress();

        $actor_source_default = config('audit-log.actor.source.enabled')
            ? self::calculateActorSource()
            : null;

        if (config('audit-log.actor.enabled')) {
            if (Auth::check()) {
                $user = Auth::user();
                $actor = [
                    'actor_email' => self::resolveActorAttribute($user, 'email'),
                    'actor_id' => self::resolveActorAttribute($user, 'id'),
                    'actor_ip_addr' => $actor_ip_addr,
                    'actor_name' => self::resolveActorAttribute($user, 'name'),
                    'actor_provider_id' => self::resolveActorAttribute($user, 'provider_id'),
                    'actor_session_id' => session()->getId(),
                    'actor_source' => $actor_source_default,
                    'actor_type' => $user::class,
                    'actor_username' => self::resolveActorAttribute($user, 'username'),
                ];
            } else {
                $actor = [
                    'actor_email' => null,
                    'actor_id' => null,
                    'actor_ip_addr' => $actor_ip_addr,
                    'actor_name' => null,
                    'actor_provider_id' => null,
                    'actor_session_id' => session()->getId(),
                    'actor_source' => $actor_source_default,
                    'actor_type' => null,
                    'actor_username' => null,
                ];
            }
        } else {
            $actor = [
                'actor_email' => null,
                'actor_id' => null,
                'actor_ip_addr' => null,
                'actor_name' => null,
                'actor_provider_id' => null,
                'actor_session_id' => null,
                'actor_source' => null,
                'actor_type' => null,
                'actor_username' => null,
            ];
        }

        $log_context = collect(get_defined_vars())
            ->only(collect($log_context_keys)->flatten(1))
            ->transform(function ($item, $key) use ($actor, $log_context_keys) {
                if ($item === null && str_starts_with($key, 'actor_')) {
                    return $actor[$key];
                }
                if ($item === null) {
                    return null;
                }
                if (in_array($key, $log_context_keys['string'])) {
                    return (string) $item;
                }
                if (in_array($key, $log_context_keys['model_type'])) {
                    return self::calculateTypeFromModel((string) $item);
                }
                if (in_array($key, $log_context_keys['int'])) {
                    return (int) $item;
                }
                if (in_array($key, $log_context_keys['array'])) {
                    return (array) $item;
                }
                if (in_array($key, $log_context_keys['date'])) {
                    return Carbon::parse($item)->toIso8601ZuluString();
                }
                if (in_array($key, $log_context_keys['ms'])) {
                    return (int) Carbon::parse($item)->diffInMilliseconds();
                }
            })->toArray();

        // Fold each computed `*_model` value into its `*_type` key. A provided
        // `*_model` takes precedence over a legacy `*_type` string; when no model
        // is provided, the legacy value (or null) is preserved.
        foreach ($log_context_keys['model_type'] as $model_key) {
            $type_key = str_replace('_model', '_type', $model_key);
            $computed = $log_context[$model_key] ?? null;
            unset($log_context[$model_key]);

            if ($computed !== null) {
                $log_context[$type_key] = $computed;
            } elseif (!array_key_exists($type_key, $log_context)) {
                $log_context[$type_key] = null;
            }
        }

        if ($log) {
            $class_line = preg_replace('/::\w+/', '', Str::afterLast($method, '\\'));
            LaravelLog::log(
                level: $level,
                message: $class_line . ' ' . $message,
                context: array_merge(
                    ['event_type' => $event_type, 'method' => $method],
                    collect($log_context)->reject(null)->toArray(),
                    [
                        'memory_current' => (int) (memory_get_usage() / 1024 / 1024) . 'MB',
                        'memory_peak' => (int) (memory_get_peak_usage() / 1024 / 1024) . 'MB'
                    ]
                )
            );
        }

        if (($database || $transaction) && config('audit-log.database.enabled')) {
            $log_data = array_merge(
                [
                    'event_type' => $event_type,
                    'level' => $level,
                    'message' => $message,
                    'method' => $method,
                    'occurred_at' => $occurred_at ? Carbon::parse($occurred_at)->toDateTimeString() : null,
                    'attribute_key' => $attribute_key,
                    'attribute_value_old' => $attribute_value_old,
                    'attribute_value_new' => $attribute_value_new,
                    'count_records' => $count_records,
                    'duration_ms_per_record' => $duration_ms_per_record,
                    'event_ms_per_record' => $event_ms_per_record,
                    'job_batch' => $job_batch,
                    'job_id' => $job_id,
                    'job_platform' => $job_platform,
                    'job_pipeline_id' => $job_pipeline_id,
                    'job_timestamp' => $job_timestamp,
                    'job_transaction_id' => $job_transaction_id,
                    // Raw fully-qualified model class name columns
                    'parent_model' => $parent_model,
                    'record_model' => $record_model,
                    'related_model' => $related_model,
                    'subject_model' => $subject_model,
                    'tenant_model' => $tenant_model,
                    // Identifier, provider, and reference columns
                    'parent_id' => $parent_id,
                    'parent_provider_id' => $parent_provider_id,
                    'parent_reference_key' => $parent_reference_key,
                    'parent_reference_value' => $parent_reference_value,
                    'record_id' => $record_id,
                    'record_provider_id' => $record_provider_id,
                    'record_reference_key' => $record_reference_key,
                    'record_reference_value' => $record_reference_value,
                    'related_id' => $related_id,
                    'subject_id' => $subject_id,
                    'tenant_id' => $tenant_id,
                ],
                // Resolved actor fields and computed `*_type` values
                collect($log_context)->only([
                    'actor_email',
                    'actor_id',
                    'actor_ip_addr',
                    'actor_name',
                    'actor_provider_id',
                    'actor_session_id',
                    'actor_source',
                    'actor_type',
                    'actor_username',
                    'parent_type',
                    'record_type',
                    'related_type',
                    'subject_type',
                    'tenant_type',
                ])->toArray()
            );

            if (!empty($errors)) {
                $log_data['errors'] = $errors;
            }

            if (!empty($metadata)) {
                $log_data['metadata'] = $metadata;
            }

            // Flatten whitelisted metadata keys into their own columns so that
            // application-specific fields can be persisted without modifying
            // this package. The keys remain in the `metadata` JSON as well.
            foreach ((array) config('audit-log.database.custom_fields', []) as $custom_field) {
                $custom_field = (string) $custom_field;
                $value = data_get($metadata, $custom_field);
                if ($value !== null) {
                    $log_data[$custom_field] = $value;
                }
            }

            self::persist($log_data);
        }

        return self::formatDump(
            dump_config: $dump_config,
            dump_date: $dump_date,
            dump_strings: $dump_strings,
            dump_keys: $dump_keys,
            log_metadata: [
                'event_type' => $event_type,
                'level' => $level,
                'message' => $message,
                'method' => $method
            ],
            log_context: $log_context
        );
    }

    /**
     * Validate the argument input
     *
     * @param array $arguments_array
     *      The array of all arguments and passed values in the handle() method
     *      Ex. get_defined_vars()
     *
     * @throws ValidationException
     */
    private static function validate($arguments_array)
    {
        $validator = Validator::make($arguments_array, [
            'actor_email' => 'nullable|string',
            'actor_id' => 'nullable|string',
            'actor_name' => 'nullable|string',
            'actor_provider_id' => 'nullable|string',
            'actor_session_id' => 'nullable|string',
            'actor_source' => 'nullable|string|in:' . implode(',', (array) config('audit-log.actor.source.allowed', [])),
            'actor_type' => 'nullable|string',
            'actor_username' => 'nullable|string',
            'event_type' => 'required|string|max:255',
            'level' => 'required|string|in:debug,info,notice,warning,error,critical,alert,emergency',
            'message' => 'required|string|max:1000',
            'method' => 'required|string|max:255',
            'attribute_key' => 'nullable|string',
            'attribute_value_old' => 'nullable|string',
            'attribute_value_new' => 'nullable|string',
            'database' => 'boolean',
            'errors' => 'array', // FIXME: Add additional sanitization
            'log' => 'boolean',
            'metadata' => 'array', // FIXME: Add additional sanitization
            'occurred_at' => 'nullable|date',
            'parent_id' => 'nullable|string',
            'parent_type' => 'nullable|string',
            'parent_model' => 'nullable|string',
            'parent_provider_id' => 'nullable|string|max:255',
            'parent_reference_key' => 'nullable|string|max:255',
            'parent_reference_value' => 'nullable|string|max:255',
            'record_id' => 'nullable|string',
            'record_type' => 'nullable|string',
            'record_model' => 'nullable|string',
            'record_provider_id' => 'nullable|string|max:255',
            'record_reference_key' => 'nullable|string|max:255',
            'record_reference_value' => 'nullable|string|max:255',
            'related_id' => 'nullable|string',
            'related_model' => 'nullable|string',
            'subject_id' => 'nullable|string',
            'subject_model' => 'nullable|string',
            'tenant_id' => 'nullable|string',
            'tenant_type' => 'nullable|string',
            'tenant_model' => 'nullable|string',
            'transaction' => 'boolean',
            'job_batch' => 'nullable|string',
            'job_id' => 'nullable|string',
            'job_platform' => 'nullable|string',
            'job_pipeline_id' => 'nullable|string',
            'job_timestamp' => 'nullable|string',
            'job_transaction_id' => 'nullable|string',
            'dump_config' => 'nullable|string',
            'dump_date' => 'nullable|string',
            'dump_keys' => 'array',
            'dump_strings' => 'array'
        ]);

        if ($validator->fails()) {
            LaravelLog::log(
                level: 'critical',
                message: __METHOD__ . ' ' . 'Validation error with incorrectly formatted arguments',
                context: [
                    'method' => __METHOD__,
                    'event_type' => 'audit.log.create.error.validation',
                    'errors' => json_encode($validator->errors()->all()),
                ]
            );

            throw new ValidationException($validator->errors()->first());
        }
    }

    /**
     * Format the response array that can be dumped into a variable
     *
     * @param ?string $dump_config
     *      The array key in config/audit.php that contains the `date`, `keys`,
     *      and `strings` schema configuration.
     *
     * @param $dump_date
     *      The PHP datetime format string for timestamps returned in array.
     *
     * @param array $dump_keys
     *      An filtered list of array of keys from the AuditLog::create() method
     *      that returned in the response array.
     *
     * @param array $dump_strings
     *      An array of key value pairs of static strings that should be
     *      included in the in the response array (instead of having to
     *      add them yourself with collection transformation later).
     *
     * @param array $log_metadata
     *      An key/value array with `event_type`, `level`, `message`, `method`
     *
     * @param array $log_context
     *      The array of log context that was used for log and transaction
     */
    private static function formatDump(
        ?string $dump_config = null,
        string $dump_date = 'c',
        array $dump_strings = [],
        array $dump_keys = [],
        array $log_metadata = [],
        array $log_context = [],
    ) {
        if (!$dump_config) {
            $dump = [
                'date' => $dump_date,
                'keys' => $dump_keys,
                'strings' => $dump_strings
            ];
        } else {
            $dump = config('audit-log.dump.' . $dump_config);
        }

        $filterable_data = collect()
            ->merge($log_metadata)
            ->merge($log_context);

        if (!empty($dump['keys'])) {
            $filtered_data = $filterable_data->only($dump['keys'])->toArray();
        } else {
            $filtered_data = $filterable_data->toArray();
        }

        return collect()
            ->merge(['datetime' => Carbon::parse($log_context['occurred_at'] ?? now())->format($dump['date'])])
            ->merge($filtered_data)
            ->merge($dump['strings'])
            ->toArray();
    }

    /**
     * Persist the audit log entry to the database
     *
     * The model is resolved from `config('audit-log.database.model')`. If the
     * model is missing/invalid or its table has not been migrated, a warning is
     * logged and persistence is skipped so that a misconfiguration never
     * prevents the calling code from completing.
     *
     * @param array<string, mixed> $log_data
     *      The key/value array of columns to persist
     */
    private static function persist(array $log_data): void
    {
        $model = config('audit-log.database.model');

        if (!is_string($model) || !class_exists($model)) {
            LaravelLog::log(
                level: 'warning',
                message: __METHOD__ . ' ' . 'Database persistence is enabled but the configured model is missing or invalid',
                context: [
                    'method' => __METHOD__,
                    'event_type' => 'audit.log.create.error.model',
                    'model' => $model,
                ]
            );

            return;
        }

        /** @var \Illuminate\Database\Eloquent\Model $instance */
        $instance = new $model;

        if (!$instance->getConnection()->getSchemaBuilder()->hasTable($instance->getTable())) {
            LaravelLog::log(
                level: 'warning',
                message: 'Audit Logs database table not found. Run the migration or disable the database integration in config/audit-log.php',
                context: [
                    'method' => __METHOD__,
                    'event_type' => 'audit.log.create.error.table',
                    'table' => $instance->getTable(),
                ]
            );

            return;
        }

        $model::create($log_data);
    }

    /**
     * Resolve an actor field value from the authenticated user model
     *
     * The user model attribute(s) mapped to each actor field are configured in
     * `config('audit-log.actor.attributes')`. A mapping may be a single
     * attribute name or an ordered list of candidates, in which case the first
     * non-null value wins.
     *
     * @param mixed $user
     *      The authenticated user model (or null)
     *
     * @param string $field
     *      The actor field key (id, email, name, provider_id, username)
     */
    private static function resolveActorAttribute($user, string $field): mixed
    {
        foreach ((array) config('audit-log.actor.attributes.' . $field, $field) as $attribute) {
            $value = data_get($user, (string) $attribute);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Resolve the actor's originating IP address
     *
     * The request headers configured in `config('audit-log.actor.ip_headers')`
     * are checked in order (useful when the application is behind a proxy or
     * CDN), falling back to `request()->ip()`.
     */
    private static function resolveActorIpAddress(): ?string
    {
        foreach ((array) config('audit-log.actor.ip_headers', []) as $header) {
            $value = request()->header((string) $header);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return request()->ip();
    }

    /**
     * Calculate the snake_case type based on a fully-qualified model class name
     *
     * The namespace configured in `config('audit-log.model.namespace')` is
     * stripped before conversion.
     *
     * @param string $modelClass
     *      The fully-qualified model class name
     *      Ex. \App\Models\Okta\User::class
     *
     * @return string The calculated snake_case type (ex. `okta_user`)
     */
    private static function calculateTypeFromModel(string $modelClass): string
    {
        $namespace = (string) config('audit-log.model.namespace', 'App\\Models\\');

        return Str::slug(Str::headline(str_replace($namespace, '', $modelClass)), '_');
    }

    /**
     * Calculate the actor source based on the current request context
     *
     * Returns one of `system` (console), `api` (API route or JSON request), or
     * `web`. The `cli` value in the allowed list must be supplied explicitly by
     * the application since it depends on application-specific token/device
     * detection.
     */
    private static function calculateActorSource(): string
    {
        if (app()->runningInConsole()) {
            return 'system';
        }

        $request = request();

        if ($request->is('api/*') || $request->is('api') || $request->expectsJson()) {
            return 'api';
        }

        return 'web';
    }
}
