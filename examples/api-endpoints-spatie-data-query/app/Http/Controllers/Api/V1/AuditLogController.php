<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\V1\AuditLog\AuditLogDetailedResponseData;
use App\Models\AuditLog as AuditLogModel;
use BoldlyGrow\AuditLog\AuditLog;
// use Dedoc\Scramble\Attributes\Group;
// use Dedoc\Scramble\Attributes\PathParameter; // Scramble Pro (paid) feature
// use Dedoc\Scramble\Attributes\QueryParameter; // Scramble Pro (paid) feature
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Copyable example — a read-only audit log API backed by Spatie Query Builder +
 * Laravel Data. See the README in this folder for install and setup notes.
 *
 * Two naming notes:
 *  - The app model `App\Models\AuditLog` (which must extend
 *    `BoldlyGrow\AuditLog\Models\AuditLog`) is aliased to `AuditLogModel` here to
 *    avoid clashing with the package's `BoldlyGrow\AuditLog\AuditLog` logger.
 *  - `dedoc/scramble` generates OpenAPI docs from the `#[Group]`,
 *    `#[QueryParameter]`, and `#[PathParameter]` attributes. They are all left
 *    COMMENTED OUT below (along with their imports) so the example has zero Scramble
 *    dependency by default — uncomment them (and their imports) to opt in. Note that
 *    `#[QueryParameter]` and `#[PathParameter]` are Scramble Pro (paid) features,
 *    while `#[Group]` is free. Alternatively, let Scramble document the Spatie
 *    filters automatically via its laravel-query-builder integration (see this
 *    folder's README).
 *
 * Encrypted filters and the `whereEncrypted*` calls rely on the base model's
 * `ModelEncryptedLookup` trait. The shipped migration indexes `id`, `event_type`,
 * `actor_id`, and every `(*_type, *_id)` and `(*_model, *_id)` pair; add your own
 * indexes for any other column you filter on heavily.
 */
// #[Group(name: 'Audit Logs', weight: 900)]
class AuditLogController
{
    /**
     * List of Audit Logs
     */
    // String filters (exact match + `_search` partial match)
    // #[QueryParameter(name: 'filter[id]', description: 'Filter by an exact match of the log ID (indexed)', example: '019f5696-0ea3-7074-8fbe-880280595f03')]
    // #[QueryParameter(name: 'filter[id_search]', description: 'Filter by a partial match of the log ID', example: '0ea3')]
    // #[QueryParameter(name: 'filter[actor_id]', description: 'Filter by an exact match of the actor ID (indexed)', example: '019f5696-0eb4-70d9-89d6-10f7035face1')]
    // #[QueryParameter(name: 'filter[actor_id_search]', description: 'Filter by a partial match of the actor ID', example: '0eb4')]
    // #[QueryParameter(name: 'filter[actor_type]', description: 'Filter by an exact match of the actor class name (indexed)', example: 'App\Models\User')]
    // #[QueryParameter(name: 'filter[actor_type_search]', description: 'Filter by a partial match of the actor class name', example: 'User')]
    // #[QueryParameter(name: 'filter[event_type]', description: 'Filter by an exact match of the event type (indexed)', example: 'user.create.success')]
    // #[QueryParameter(name: 'filter[event_type_search]', description: 'Filter by a partial match of the event type', example: 'create')]
    // #[QueryParameter(name: 'filter[level]', description: 'Filter by an exact match of the log level', example: 'info')]
    // #[QueryParameter(name: 'filter[level_search]', description: 'Filter by a partial match of the log level', example: 'inf')]
    // #[QueryParameter(name: 'filter[tenant_id]', description: 'Filter by an exact match of the tenant ID', example: '019f5696-0f02-725f-961a-9640a6225d91')]
    // #[QueryParameter(name: 'filter[tenant_type]', description: 'Filter by an exact match of the tenant type (indexed)', example: 'okta_organization')]
    // #[QueryParameter(name: 'filter[parent_id]', description: 'Filter by an exact match of the parent ID', example: '019f5696-0ebd-71ae-960d-1fd04ec6aad0')]
    // #[QueryParameter(name: 'filter[parent_type]', description: 'Filter by an exact match of the parent type (indexed)', example: 'okta_group')]
    // #[QueryParameter(name: 'filter[record_id]', description: 'Filter by an exact match of the record ID (indexed)', example: '019f5696-0ec6-7241-beeb-ead3db1bd664')]
    // #[QueryParameter(name: 'filter[record_id_search]', description: 'Filter by a partial match of the record ID', example: '0ec6')]
    // #[QueryParameter(name: 'filter[record_type]', description: 'Filter by an exact match of the record type (indexed)', example: 'okta_user')]
    // #[QueryParameter(name: 'filter[record_type_search]', description: 'Filter by a partial match of the record type', example: 'user')]
    // #[QueryParameter(name: 'filter[record_provider_id]', description: 'Filter by an exact match of the record provider ID', example: '019f5696-0ecf-732e-a80e-9a7111445cc0')]
    // #[QueryParameter(name: 'filter[related_id]', description: 'Filter by an exact match of the related ID', example: '019f5696-0ed6-73f4-881e-1300dec1d1f9')]
    // #[QueryParameter(name: 'filter[related_type]', description: 'Filter by an exact match of the related type (indexed)', example: 'okta_group_rule')]
    // #[QueryParameter(name: 'filter[subject_id]', description: 'Filter by an exact match of the subject ID', example: '019f5696-0edf-729a-bdde-794d8c569b3a')]
    // #[QueryParameter(name: 'filter[subject_type]', description: 'Filter by an exact match of the subject type (indexed)', example: 'okta_user')]
    // #[QueryParameter(name: 'filter[actor_session_id]', description: 'Filter by an exact match of the actor session ID', example: 'a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6')]
    // #[QueryParameter(name: 'filter[attribute_key]', description: 'Filter by an exact match of the attribute key', example: 'name')]
    // #[QueryParameter(name: 'filter[count_records]', description: 'Filter by an exact match of the count of records', example: '5')]
    // #[QueryParameter(name: 'filter[job_batch]', description: 'Filter by an exact match of the job batch', example: '019f5696-0ee8-728f-a27d-02163c8abf72')]
    // #[QueryParameter(name: 'filter[job_id]', description: 'Filter by an exact match of the job ID', example: '019f5696-0ef1-7356-b1be-1b7d8e27177e')]
    // #[QueryParameter(name: 'filter[job_platform]', description: 'Filter by an exact match of the job platform', example: 'aws-lambda')]
    // #[QueryParameter(name: 'filter[job_pipeline_id]', description: 'Filter by an exact match of the job pipeline ID', example: '123456789')]
    // #[QueryParameter(name: 'filter[job_transaction_id]', description: 'Filter by an exact match of the job transaction ID', example: '019f5696-0efa-711a-8985-28b6a4bdb28d')]
    // #[QueryParameter(name: 'filter[message]', description: 'Filter by an exact match of the message', example: 'User created successfully')]
    // #[QueryParameter(name: 'filter[message_search]', description: 'Filter by a partial match of the message', example: 'created')]
    // #[QueryParameter(name: 'filter[method]', description: 'Filter by an exact match of the method', example: 'App\Actions\CreateUser::handle:42')]
    // #[QueryParameter(name: 'filter[method_search]', description: 'Filter by a partial match of the method', example: 'CreateUser')]
    // Encrypted filters (decrypt in PHP via the base model's encrypted-search scopes)
    // #[QueryParameter(name: 'filter[actor_name_search]', description: 'Filter by a partial match of the actor name (encrypted)', example: 'Murphy')]
    // #[QueryParameter(name: 'filter[actor_username_search]', description: 'Filter by a partial match of the actor username (encrypted)', example: 'dmurphy')]
    // #[QueryParameter(name: 'filter[attribute_value_old]', description: 'Filter by an exact match of the old attribute value (encrypted)', example: 'IT Team')]
    // #[QueryParameter(name: 'filter[attribute_value_old_search]', description: 'Filter by a partial match of the old attribute value (encrypted)', example: 'IT')]
    // #[QueryParameter(name: 'filter[attribute_value_new]', description: 'Filter by an exact match of the new attribute value (encrypted)', example: 'Security Team')]
    // #[QueryParameter(name: 'filter[attribute_value_new_search]', description: 'Filter by a partial match of the new attribute value (encrypted)', example: 'Security')]
    // #[QueryParameter(name: 'filter[metadata_search]', description: 'Search metadata for any partial string match (encrypted)', example: 'value1')]
    // Date range filters
    // #[QueryParameter(name: 'filter[occurred_before]', description: 'Filter by logs that occurred on or before a date', example: '2026-01-15')]
    // #[QueryParameter(name: 'filter[occurred_after]', description: 'Filter by logs that occurred on or after a date', example: '2026-01-01')]
    // #[QueryParameter(name: 'filter[created_before]', description: 'Filter by logs created on or before a date', example: '2026-01-15')]
    // #[QueryParameter(name: 'filter[created_after]', description: 'Filter by logs created on or after a date', example: '2026-01-01')]
    // #[QueryParameter(name: 'filter[deleted_before]', description: 'Filter by logs deleted on or before a date (trashed only)', example: '2026-01-15')]
    // #[QueryParameter(name: 'filter[deleted_after]', description: 'Filter by logs deleted on or after a date (trashed only)', example: '2026-01-01')]
    // #[QueryParameter(name: 'filter[trashed]', description: 'Include or restrict soft-deleted rows (with, only, or empty for none)', example: 'only')]
    public function list(Request $request)
    {
        $event_ms = now();

        $records = QueryBuilder::for(AuditLogModel::class)
            ->allowedFilters([
                // String filters (exact + partial)
                AllowedFilter::exact('id'),
                AllowedFilter::partial('id_search', 'id'),
                AllowedFilter::exact('actor_id'),
                AllowedFilter::partial('actor_id_search', 'actor_id'),
                AllowedFilter::exact('actor_type'),
                AllowedFilter::partial('actor_type_search', 'actor_type'),
                AllowedFilter::exact('event_type'),
                AllowedFilter::partial('event_type_search', 'event_type'),
                AllowedFilter::exact('level'),
                AllowedFilter::partial('level_search', 'level'),
                AllowedFilter::exact('tenant_id'),
                AllowedFilter::exact('tenant_type'),
                AllowedFilter::exact('parent_id'),
                AllowedFilter::exact('parent_type'),
                AllowedFilter::exact('record_id'),
                AllowedFilter::partial('record_id_search', 'record_id'),
                AllowedFilter::exact('record_type'),
                AllowedFilter::partial('record_type_search', 'record_type'),
                AllowedFilter::exact('record_provider_id'),
                AllowedFilter::exact('related_id'),
                AllowedFilter::exact('related_type'),
                AllowedFilter::exact('subject_id'),
                AllowedFilter::exact('subject_type'),
                AllowedFilter::exact('actor_session_id'),
                AllowedFilter::exact('attribute_key'),
                AllowedFilter::exact('count_records'),
                AllowedFilter::exact('job_batch'),
                AllowedFilter::exact('job_id'),
                AllowedFilter::exact('job_platform'),
                AllowedFilter::exact('job_pipeline_id'),
                AllowedFilter::exact('job_transaction_id'),
                AllowedFilter::exact('message'),
                AllowedFilter::partial('message_search', 'message'),
                AllowedFilter::exact('method'),
                AllowedFilter::partial('method_search', 'method'),

                // Encrypted filters (callback into the encrypted-search scopes)
                AllowedFilter::callback('actor_name_search', function (Builder $query, $value) {
                    return $query->whereEncryptedStringPartial(key: 'actor_name', value: $value);
                }),
                AllowedFilter::callback('actor_username_search', function (Builder $query, $value) {
                    return $query->whereEncryptedStringPartial(key: 'actor_username', value: $value);
                }),
                AllowedFilter::callback('attribute_value_old', function (Builder $query, $value) {
                    return $query->whereEncryptedStringExact(key: 'attribute_value_old', value: $value);
                }),
                AllowedFilter::callback('attribute_value_old_search', function (Builder $query, $value) {
                    return $query->whereEncryptedStringPartial(key: 'attribute_value_old', value: $value);
                }),
                AllowedFilter::callback('attribute_value_new', function (Builder $query, $value) {
                    return $query->whereEncryptedStringExact(key: 'attribute_value_new', value: $value);
                }),
                AllowedFilter::callback('attribute_value_new_search', function (Builder $query, $value) {
                    return $query->whereEncryptedStringPartial(key: 'attribute_value_new', value: $value);
                }),
                AllowedFilter::callback('metadata_search', function (Builder $query, $value) {
                    return $query->whereEncryptedArraySearch(column: 'metadata', search: $value);
                }),

                // Date range filters (backed by the base model's query scopes)
                AllowedFilter::scope('occurred_before', 'occurredBefore'),
                AllowedFilter::scope('occurred_after', 'occurredAfter'),
                AllowedFilter::scope('created_before', 'createdBefore'),
                AllowedFilter::scope('created_after', 'createdAfter'),
                AllowedFilter::scope('deleted_before', 'deletedBefore'),
                AllowedFilter::scope('deleted_after', 'deletedAfter'),
                AllowedFilter::trashed(),
            ])
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25))
            ->withQueryString();

        AuditLog::create(
            count_records: $records->count(),
            event_ms: $event_ms,
            event_type: 'audit.log.list.success',
            level: 'info',
            log: true,
            message: 'API Query for List of Audit Logs',
            metadata: [
                'filter' => $request->get(key: 'filter', default: []),
                'page' => $request->get(key: 'page'),
                'sort' => explode(',', (string) $request->get(key: 'sort', default: '')),
            ],
            method: __METHOD__ . ':' . __LINE__,
            record_model: AuditLogModel::class,
            database: false,
        );

        return AuditLogDetailedResponseData::collect($records);
    }

    /**
     * Describe an Audit Log
     *
     * Uses implicit route-model binding, which excludes soft-deleted rows. To let
     * this endpoint describe trashed logs too, register an explicit binding that
     * calls `withTrashed()` (see the routes file in this folder).
     */
    // #[PathParameter('log', description: 'Audit Log ID', example: '019f5696-0ea3-7074-8fbe-880280595f03')]
    public function describe(AuditLogModel $log, Request $request)
    {
        $event_ms = now();

        AuditLog::create(
            event_ms: $event_ms,
            event_type: 'audit.log.describe.success',
            level: 'info',
            log: true,
            message: 'API Query for Describing an Audit Log',
            metadata: [],
            method: __METHOD__ . ':' . __LINE__,
            record_id: $log->getKey(),
            record_model: AuditLogModel::class,
            database: false,
        );

        return AuditLogDetailedResponseData::fromModel($log);
    }
}
