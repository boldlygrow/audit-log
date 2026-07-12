<?php

/**
 * @copyright Jefferson Martin
 * @license MIT <https://spdx.org/licenses/MIT.html>
 *
 * @link https://github.com/boldlygrow/audit-log
 */

namespace BoldlyGrow\AuditLog\Models;

use BoldlyGrow\AuditLog\Exceptions\ImmutableRecordException;
use BoldlyGrow\AuditLog\Traits\ImmutableRecords;
use BoldlyGrow\AuditLog\Traits\ModelEncryptedLookup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Audit Log entry model.
 *
 * This is the concrete base model used to persist entries created by
 * {@see \BoldlyGrow\AuditLog\AuditLog::create()} when `database: true` is passed
 * and `config('audit-log.database.enabled')` is true. It works out of the box.
 *
 * ## Extending
 *
 * To add application-specific relationships, casts, or scopes, create your own
 * model (for example `App\Models\AuditLog`) that extends this class and point
 * `config('audit-log.database.model')` at it. Reference your model from your UI
 * and API code; extending the base means package upgrades flow through
 * automatically.
 *
 * ## Polymorphic relationships
 *
 * Each `*_model` column stores the fully-qualified model class name (FQCN),
 * while the paired `*_type` column stores a human-friendly snake_case string.
 * The relationships below morph on the `*_model` (FQCN) columns so no morph map
 * is required. If you prefer a morph map keyed on the `*_type` string, override
 * these methods in your model and register the map via `Relation::enforceMorphMap()`.
 *
 * The `subject_model` column is intentionally generic — it may reference any
 * model in the consuming application (including a custom module), so the
 * `subject()` relationship is not constrained to a single class.
 *
 * @property int                       $id
 * ## Immutability
 *
 * Via the {@see ImmutableRecords} trait, updates and permanent deletes are gated
 * by `config('audit-log.immutable.update')` and `.destroy` (both default true) —
 * a blocked operation throws {@see ImmutableRecordException}.
 * Soft deletes are always allowed, and every mutation (or attempt, when allowed)
 * is itself recorded as a new audit entry.
 *
 * @property string|null               $event_type
 * @property string|null               $level
 * @property string|null               $message
 * @property string|null               $method
 * @property Carbon|null               $occurred_at
 * @property string|null               $actor_email
 * @property string|null               $actor_id
 * @property string|null               $actor_ip_addr
 * @property string|null               $actor_name
 * @property string|null               $actor_provider_id
 * @property string|null               $actor_session_id
 * @property string|null               $actor_source
 * @property string|null               $actor_type
 * @property string|null               $actor_username
 * @property string|null               $attribute_key
 * @property string|null               $attribute_value_old
 * @property string|null               $attribute_value_new
 * @property int|null                  $count_records
 * @property int|null                  $duration_ms_per_record
 * @property int|null                  $event_ms_per_record
 * @property string|null               $parent_id
 * @property string|null               $parent_model
 * @property string|null               $parent_type
 * @property string|null               $parent_provider_id
 * @property string|null               $parent_reference_key
 * @property string|null               $parent_reference_value
 * @property string|null               $record_id
 * @property string|null               $record_model
 * @property string|null               $record_type
 * @property string|null               $record_provider_id
 * @property string|null               $record_reference_key
 * @property string|null               $record_reference_value
 * @property string|null               $related_id
 * @property string|null               $related_model
 * @property string|null               $related_type
 * @property string|null               $subject_id
 * @property string|null               $subject_model
 * @property string|null               $subject_type
 * @property string|null               $tenant_id
 * @property string|null               $tenant_model
 * @property string|null               $tenant_type
 * @property string|null               $job_batch
 * @property string|null               $job_id
 * @property string|null               $job_platform
 * @property string|null               $job_pipeline_id
 * @property Carbon|null               $job_timestamp
 * @property string|null               $job_transaction_id
 * @property array<string, mixed>|null $errors
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null               $created_at
 * @property Carbon|null               $updated_at
 * @property Carbon|null               $deleted_at
 */
class AuditLog extends Model
{
    use ImmutableRecords;
    use ModelEncryptedLookup;
    use SoftDeletes {
        restore as protected restoreWithoutTransaction;
    }

    /**
     * The attributes that are guarded from mass assignment.
     *
     * @var array<int, string>
     */
    protected $guarded = [];


    /**
     * Save the model, wrapping an update in a transaction.
     *
     * The {@see ImmutableRecords} trait records every mutation as a new audit
     * entry; wrapping the update and that entry in one transaction guarantees a
     * record is never changed without its log — if either fails, both roll back.
     * Inserts (including the audit entries themselves) record no mutation, so they
     * skip the wrapper.
     *
     * @param  array<string, mixed>  $options
     */
    public function save(array $options = [])
    {
        if (! $this->exists) {
            return parent::save($options);
        }

        return $this->getConnection()->transaction(fn () => parent::save($options));
    }

    /**
     * Delete the model, wrapping the delete and its recorded audit entry in a
     * single transaction. Covers both soft deletes and force deletes (the latter
     * calls delete() internally).
     */
    public function delete()
    {
        return $this->getConnection()->transaction(fn () => parent::delete());
    }

    /**
     * Restore a soft-deleted model, wrapping the restore and its recorded audit
     * entry in a single transaction.
     */
    public function restore()
    {
        return $this->getConnection()->transaction(fn () => $this->restoreWithoutTransaction());
    }

    /**
     * Resolve the table name from configuration so that consuming applications
     * can rename it in `config('audit-log.database.table')`.
     */
    public function getTable(): string
    {
        return $this->table ?? (string) config('audit-log.database.table', 'audit_logs');
    }

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Event metadata
            'event_type' => 'string',
            'level' => 'string',
            'message' => 'string',
            'method' => 'string',
            'occurred_at' => 'datetime',

            // Actor
            'actor_email' => 'encrypted',
            'actor_id' => 'string',
            'actor_ip_addr' => 'string',
            'actor_name' => 'encrypted',
            'actor_provider_id' => 'string',
            'actor_session_id' => 'string',
            'actor_source' => 'string',
            'actor_type' => 'string',
            'actor_username' => 'encrypted',

            // State changes
            'attribute_key' => 'string',
            'attribute_value_old' => 'encrypted',
            'attribute_value_new' => 'encrypted',

            // Counts and durations
            'count_records' => 'integer',
            'duration_ms_per_record' => 'integer',
            'event_ms_per_record' => 'integer',

            // Parent
            'parent_id' => 'string',
            'parent_model' => 'string',
            'parent_type' => 'string',
            'parent_provider_id' => 'string',
            'parent_reference_key' => 'string',
            'parent_reference_value' => 'encrypted',

            // Record
            'record_id' => 'string',
            'record_model' => 'string',
            'record_type' => 'string',
            'record_provider_id' => 'string',
            'record_reference_key' => 'string',
            'record_reference_value' => 'encrypted',

            // Related
            'related_id' => 'string',
            'related_model' => 'string',
            'related_type' => 'string',

            // Subject
            'subject_id' => 'string',
            'subject_model' => 'string',
            'subject_type' => 'string',

            // Tenant
            'tenant_id' => 'string',
            'tenant_model' => 'string',
            'tenant_type' => 'string',

            // Background job metadata
            'job_batch' => 'string',
            'job_id' => 'string',
            'job_platform' => 'string',
            'job_pipeline_id' => 'string',
            'job_timestamp' => 'datetime',
            'job_transaction_id' => 'string',

            // Freeform payloads
            'errors' => 'array',
            'metadata' => 'encrypted:array',
        ];
    }

    /**
     * The actor (user, system, or service account) that performed the action.
     *
     * Morphs on the `actor_type` column, which stores the fully-qualified class
     * name of the authenticated user model.
     *
     * @return MorphTo<Model, $this>
     */
    public function actor(): MorphTo
    {
        return $this->morphTo(name: 'actor', type: 'actor_type', id: 'actor_id');
    }

    /**
     * The parent record (for many-to-many relationship events).
     *
     * @return MorphTo<Model, $this>
     */
    public function parent(): MorphTo
    {
        return $this->morphTo(name: 'parent', type: 'parent_model', id: 'parent_id');
    }

    /**
     * The affected record.
     *
     * @return MorphTo<Model, $this>
     */
    public function record(): MorphTo
    {
        return $this->morphTo(name: 'record', type: 'record_model', id: 'record_id');
    }

    /**
     * A related human or service account.
     *
     * @return MorphTo<Model, $this>
     */
    public function related(): MorphTo
    {
        return $this->morphTo(name: 'related', type: 'related_model', id: 'related_id');
    }

    /**
     * The impacted subject account. Kept generic so any application module's
     * model may be referenced.
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo(name: 'subject', type: 'subject_model', id: 'subject_id');
    }

    /**
     * The top-level organization/tenant entity for the provider.
     *
     * @return MorphTo<Model, $this>
     */
    public function tenant(): MorphTo
    {
        return $this->morphTo(name: 'tenant', type: 'tenant_model', id: 'tenant_id');
    }

    /**
     * Get records that were created before a specific date
     *
     * @param  string  $date  A Carbon parsable date (most date and datetime formats allowed)
     */
    public function scopeCreatedBefore(Builder $query, $date): Builder
    {
        return $query->where('created_at', '<=', Carbon::parse($date));
    }

    /**
     * Get records that were created after a specific date
     *
     * @param  string  $date  A Carbon parsable date (most date and datetime formats allowed)
     */
    public function scopeCreatedAfter(Builder $query, $date): Builder
    {
        return $query->where('created_at', '>=', Carbon::parse($date));
    }

    /**
     * Get records that occurred before a specific date
     *
     * @param  string  $date  A Carbon parsable date (most date and datetime formats allowed)
     */
    public function scopeOccurredBefore(Builder $query, $date): Builder
    {
        return $query->where('occurred_at', '<=', Carbon::parse($date));
    }

    /**
     * Get records that occurred after a specific date
     *
     * @param  string  $date  A Carbon parsable date (most date and datetime formats allowed)
     */
    public function scopeOccurredAfter(Builder $query, $date): Builder
    {
        return $query->where('occurred_at', '>=', Carbon::parse($date));
    }

    /**
     * Get only soft deleted records that were deactivated or expired before a specific date
     *
     * @param  string  $date  A Carbon parsable date (most date and datetime formats allowed)
     */
    public function scopeDeletedBefore(Builder $query, $date): Builder
    {
        return $query->onlyTrashed()->where('deleted_at', '<=', Carbon::parse($date));
    }

    /**
     * Get only soft deleted records that were deactivated or expired after a specific date
     *
     * @param  string  $date  A Carbon parsable date (most date and datetime formats allowed)
     */
    public function scopeDeletedAfter(Builder $query, $date): Builder
    {
        return $query->onlyTrashed()->where('deleted_at', '>=', Carbon::parse($date));
    }
}
