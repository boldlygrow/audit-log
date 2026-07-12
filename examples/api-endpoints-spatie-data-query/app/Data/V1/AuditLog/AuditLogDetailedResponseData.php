<?php

namespace App\Data\V1\AuditLog;

use App\Models\AuditLog;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

/**
 * Copyable example — genericized from a production implementation.
 *
 * Exposes an audit log record as a typed API response. It intentionally surfaces
 * the human-friendly snake_case `*_type` strings (e.g. `okta_user`) rather than
 * the fully-qualified `*_model` class names, so API/SIEM/CSV consumers can filter
 * on stable, readable values.
 *
 * ⚠️  PII WARNING: `actor_name`, `actor_username`, `attribute_value_old`,
 * `attribute_value_new`, and `metadata` map to columns the package encrypts at
 * rest. They decrypt transparently when the model is read, so exposing them here
 * returns plaintext. Apply your own authorization and/or redaction before
 * returning this payload to a client.
 *
 * Additional columns the package persists that are NOT exposed below — add them
 * if your use case needs them: `actor_email`, `actor_ip_addr`,
 * `actor_provider_id`, `parent_provider_id`, `parent_reference_key`,
 * `parent_reference_value`, `record_reference_key`, `record_reference_value`,
 * `duration_ms_per_record`, `event_ms_per_record`, `updated_at`, `deleted_at`.
 */
class AuditLogDetailedResponseData extends Data
{
    public function __construct(
        /**
         * @example 019f5696-0ea3-7074-8fbe-880280595f03
         */
        public string $id,
        /**
         * The ID of the top-level organization/tenant the event belongs to.
         *
         * @example 019f5696-0eab-7339-a25a-1e870eb03aee
         */
        public ?string $tenant_id,
        /**
         * The human-friendly type of the tenant entity.
         *
         * @example okta_organization
         */
        public ?string $tenant_type,
        /**
         * The timestamp when the audit log was created.
         *
         * @example 2026-01-15T10:30:00Z
         */
        public CarbonImmutable $created_at,
        /**
         * The timestamp when the event occurred.
         *
         * @example 2026-01-15T10:30:00Z
         */
        public ?CarbonImmutable $occurred_at,
        /**
         * The industry standard log severity level.
         *
         * @example info
         */
        public ?string $level,
        /**
         * The type of event (e.g., namespace.resource.child_resource?.action.result?.context?).
         *
         * @example user.create.success
         */
        public ?string $event_type,
        /**
         * The method or action that triggered the log entry and line number.
         *
         * @example App\Actions\CreateUser::handle:42
         */
        public ?string $method,
        /**
         * The log message describing the action.
         *
         * @example User created successfully
         */
        public ?string $message,
        /**
         * Error messages associated with the action.
         *
         * @example {"email": "Invalid input"}
         */
        public ?array $errors,
        /**
         * Additional metadata associated with the log entry. This provides context that the ID and type may not cover.
         *
         * @example {"key1": "value1", "key2": "value2"}
         */
        public ?array $metadata,
        /**
         * The fully-qualified class name of the actor who performed the action.
         *
         * @example App\Models\User
         */
        public ?string $actor_type,
        /**
         * The ID of the actor who performed the action.
         *
         * @example 019f5696-0eb4-70d9-89d6-10f7035face1
         */
        public ?string $actor_id,
        /**
         * The name of the actor who performed the action.
         *
         * @example Dade Murphy
         */
        public ?string $actor_name,
        /**
         * The username of the actor that performed the action.
         *
         * @example dmurphy
         */
        public ?string $actor_username,
        /**
         * The session ID of the actor.
         *
         * @example a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
         */
        public ?string $actor_session_id,
        /**
         * The origin of the request (system, api, web, cli).
         *
         * @example web
         */
        public ?string $actor_source,
        /**
         * The snake_case or camelCase key in the database table (or API response) that was modified. This provides
         * a before and after snapshot for database column or array values that are changed.
         *
         * @example name
         */
        public ?string $attribute_key,
        /**
         * The old database or API response value of the attribute before modification. Null for created records.
         *
         * @example IT Team
         */
        public ?string $attribute_value_old,
        /**
         * The new value of the attribute after modification.
         *
         * @example Security Team
         */
        public ?string $attribute_value_new,
        /**
         * The type of the parent resource.
         *
         * @example okta_group
         */
        public ?string $parent_type,
        /**
         * The ID of the parent resource.
         *
         * @example 019f5696-0ebd-71ae-960d-1fd04ec6aad0
         */
        public ?string $parent_id,
        /**
         * The type of the record being acted upon.
         *
         * @example okta_user
         */
        public ?string $record_type,
        /**
         * The ID of the record being acted upon.
         *
         * @example 019f5696-0ec6-7241-beeb-ead3db1bd664
         */
        public ?string $record_id,
        /**
         * The integration provider API identifier of the record (if applicable).
         *
         * @example 019f5696-0ecf-732e-a80e-9a7111445cc0
         */
        public ?string $record_provider_id,
        /**
         * The ID of the related resource.
         *
         * @example 019f5696-0ed6-73f4-881e-1300dec1d1f9
         */
        public ?string $related_id,
        /**
         * The type of the related resource.
         *
         * @example okta_group_rule
         */
        public ?string $related_type,
        /**
         * The ID of the subject related to the action.
         *
         * @example 019f5696-0edf-729a-bdde-794d8c569b3a
         */
        public ?string $subject_id,
        /**
         * The type of the subject. The record type is what was acted upon; the subject is on behalf of whom.
         *
         * @example okta_user
         */
        public ?string $subject_type,
        /**
         * The count of records affected by this action (if applicable for bulk operations).
         *
         * @example 5
         */
        public ?int $count_records,
        /**
         * The batch identifier for grouped job operations.
         *
         * @example 019f5696-0ee8-728f-a27d-02163c8abf72
         */
        public ?string $job_batch,
        /**
         * The job ID associated with this log entry.
         *
         * @example 019f5696-0ef1-7356-b1be-1b7d8e27177e
         */
        public ?string $job_id,
        /**
         * The platform where the job was executed.
         *
         * @example aws-lambda
         */
        public ?string $job_platform,
        /**
         * The pipeline ID for CI/CD operations.
         *
         * @example 123456789
         */
        public ?string $job_pipeline_id,
        /**
         * The timestamp when the job was executed.
         *
         * @example 2026-01-15T10:30:00Z
         */
        public ?Carbon $job_timestamp,
        /**
         * The transaction ID for the job.
         *
         * @example 019f5696-0efa-711a-8985-28b6a4bdb28d
         */
        public ?string $job_transaction_id,
    ) {}

    public static function fromModel(AuditLog $log): self
    {
        return new self(
            id: $log->id,
            tenant_id: $log->tenant_id,
            tenant_type: $log->tenant_type,
            created_at: $log->created_at->timezone('UTC')->toImmutable(),
            occurred_at: $log->occurred_at ? $log->occurred_at->timezone('UTC')->toImmutable() : null,
            level: $log->level,
            event_type: $log->event_type,
            method: $log->method,
            message: $log->message,
            errors: $log->errors,
            metadata: $log->metadata,
            actor_type: $log->actor_type,
            actor_id: $log->actor_id,
            actor_name: $log->actor_name,
            actor_username: $log->actor_username,
            actor_session_id: $log->actor_session_id,
            actor_source: $log->actor_source,
            attribute_key: $log->attribute_key,
            attribute_value_old: $log->attribute_value_old,
            attribute_value_new: $log->attribute_value_new,
            parent_type: $log->parent_type,
            parent_id: $log->parent_id,
            record_type: $log->record_type,
            record_id: $log->record_id,
            record_provider_id: $log->record_provider_id,
            related_id: $log->related_id,
            related_type: $log->related_type,
            subject_id: $log->subject_id,
            subject_type: $log->subject_type,
            count_records: $log->count_records,
            job_batch: $log->job_batch,
            job_id: $log->job_id,
            job_platform: $log->job_platform,
            job_pipeline_id: $log->job_pipeline_id,
            job_timestamp: $log->job_timestamp,
            job_transaction_id: $log->job_transaction_id,
        );
    }
}
