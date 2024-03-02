<?php

/**
 * @copyright Jefferson Martin
 * @license MIT <https://spdx.org/licenses/MIT.html>
 * @link https://gitlab.com/provisionesta/audit
 */

namespace Provisionesta\Audit;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log as LaravelLog;
use Illuminate\Support\Facades\Validator;
use Provisionesta\Audit\Exceptions\ValidationException;

class Log
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
     * @param ?string $dump_config (optional)
     *      The array key in config/audit.php that contains the `date`, `keys`,
     *      and `strings` schema configuration.
     *
     * @param $dump_date (optional)
     *      The PHP datetime format string for timestamps returned in array.
     *      (default: 'c')
     *
     * @param array $dump_keys (optional)
     *      An filtered list of array of keys from the Log::create() method
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
     * @param bool $transaction (optional)
     *      Whether to create a Transaction database entry for this event
     *      (default: false)
     */
    public static function create(
        string $event_type,
        string $level,
        string $message,
        string $method,
        ?string $attribute_key = null,
        ?string $attribute_value_old = null,
        ?string $attribute_value_new = null,
        ?int $count_records = null,
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
        ?string $parent_provider_id = null,
        ?string $parent_reference_key = null,
        ?string $parent_reference_value = null,
        ?string $record_id = null,
        ?string $record_type = null,
        ?string $record_provider_id = null,
        ?string $record_reference_key = null,
        ?string $record_reference_value = null,
        ?string $tenant_id = null,
        ?string $tenant_type = null,
        bool $transaction = false,
    ): array {
        self::validate(get_defined_vars());

        $log_context_keys = [
            'string' => [
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

        $log_context = collect(get_defined_vars())
            ->only(collect($log_context_keys)->flatten(1))
            ->transform(function ($item, $key) use ($log_context_keys) {
                if ($item === null) {
                    return null;
                }
                if (in_array($key, $log_context_keys['string'])) {
                    return (string) $item;
                }
                if (in_array($key, $log_context_keys['int'])) {
                    return (int) $item;
                }
                if (in_array($key, $log_context_keys['array'])) {
                    return (array) $item;
                }
                if (in_array($key, $log_context_keys['date'])) {
                    return Carbon::parse($item)->toIso8601String();
                }
                if (in_array($key, $log_context_keys['ms'])) {
                    return (int) Carbon::parse($item)->diffInMilliseconds();
                }
            })->toArray();

        if ($log) {
            $message_array = explode('\\', $method);
            LaravelLog::log(
                level: $level,
                message: end($message_array) . ' ' . $message,
                context: array_merge(
                    ['event_type' => $event_type, 'method' => $method],
                    collect($log_context)->reject(null)->toArray()
                )
            );
        }

        // if ($transaction) {
        //     CreateTransaction::make()->handle(
        //         attribute_key: $attribute_key,
        //         attribute_value_old: $attribute_value_old,
        //         attribute_value_new: $attribute_value_new,
        //         count_records: $count_records,
        //         errors: $errors,
        //         event_type: $event_type,
        //         job_batch: $job_batch,
        //         job_id: $job_id,
        //         job_platform: $job_platform,
        //         job_pipeline_id: $job_pipeline_id,
        //         job_timestamp: $job_timestamp,
        //         job_transaction_id: $job_transaction_id,
        //         level: $level,
        //         message: $message,
        //         metadata: $metadata,
        //         method: $method,
        //         occurred_at: $occurred_at ? Carbon::parse($occurred_at)->toDateTimeString() : null,
        //         parent_id: $parent_id,
        //         parent_type: $parent_type,
        //         record_id: $record_id,
        //         record_type: $record_type,
        //         record_provider_id: $record_provider_id,
        //         tenant_id: $tenant_id,
        //         tenant_type: $tenant_type
        //     );
        // }

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
            'event_type' => 'required|string|max:255',
            'level' => 'required|string|in:debug,info,notice,warning,error,critical,alert,emergency',
            'message' => 'required|string|max:1000',
            'method' => 'required|string|max:255',
            'attribute_key' => 'nullable|string',
            'attribute_value_old' => 'nullable|string',
            'attribute_value_new' => 'nullable|string',
            'errors' => 'array', // FIXME: Add additional sanitization
            'log' => 'boolean',
            'metadata' => 'array', // FIXME: Add additional sanitization
            'occurred_at' => 'nullable|date',
            'parent_id' => 'nullable|uuid',
            'parent_type' => 'nullable|string',
            'parent_provider_id' => 'nullable|string|max:255',
            'parent_reference_key' => 'nullable|string|max:255',
            'parent_reference_value' => 'nullable|string|max:255',
            'record_id' => 'nullable|uuid',
            'record_type' => 'nullable|string',
            'record_provider_id' => 'nullable|string|max:255',
            'record_reference_key' => 'nullable|string|max:255',
            'record_reference_value' => 'nullable|string|max:255',
            'tenant_id' => 'nullable|uuid',
            'tenant_type' => 'nullable|string',
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
     *      An filtered list of array of keys from the Log::create() method
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
            $dump = config('audit.dump.' . $dump_config);
        }

        $filterable_data = collect()
            ->merge($log_metadata)
            ->merge($log_context);

        if (!empty($dump_keys)) {
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
}
