<?php

/**
 * @copyright Jefferson Martin
 * @license MIT <https://spdx.org/licenses/MIT.html>
 * @link https://github.com/boldlygrow/audit-log
 */

namespace BoldlyGrow\AuditLog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @property int                             $id
 * @property string|null                     $event_type
 * @property string|null                     $level
 * @property string|null                     $message
 * @property string|null                     $method
 * @property \Illuminate\Support\Carbon|null $occurred_at
 * @property string|null                     $actor_email
 * @property string|null                     $actor_id
 * @property string|null                     $actor_ip_addr
 * @property string|null                     $actor_name
 * @property string|null                     $actor_provider_id
 * @property string|null                     $actor_session_id
 * @property string|null                     $actor_source
 * @property string|null                     $actor_type
 * @property string|null                     $actor_username
 * @property string|null                     $attribute_key
 * @property string|null                     $attribute_value_old
 * @property string|null                     $attribute_value_new
 * @property int|null                        $count_records
 * @property int|null                        $duration_ms_per_record
 * @property int|null                        $event_ms_per_record
 * @property string|null                     $parent_id
 * @property string|null                     $parent_model
 * @property string|null                     $parent_type
 * @property string|null                     $parent_provider_id
 * @property string|null                     $parent_reference_key
 * @property string|null                     $parent_reference_value
 * @property string|null                     $record_id
 * @property string|null                     $record_model
 * @property string|null                     $record_type
 * @property string|null                     $record_provider_id
 * @property string|null                     $record_reference_key
 * @property string|null                     $record_reference_value
 * @property string|null                     $related_id
 * @property string|null                     $related_model
 * @property string|null                     $related_type
 * @property string|null                     $subject_id
 * @property string|null                     $subject_model
 * @property string|null                     $subject_type
 * @property string|null                     $tenant_id
 * @property string|null                     $tenant_model
 * @property string|null                     $tenant_type
 * @property string|null                     $job_batch
 * @property string|null                     $job_id
 * @property string|null                     $job_platform
 * @property string|null                     $job_pipeline_id
 * @property \Illuminate\Support\Carbon|null $job_timestamp
 * @property string|null                     $job_transaction_id
 * @property array<string, mixed>|null       $errors
 * @property array<string, mixed>|null       $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class AuditLog extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are guarded from mass assignment.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

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
            'occurred_at' => 'datetime',
            'job_timestamp' => 'datetime',
            'count_records' => 'integer',
            'duration_ms_per_record' => 'integer',
            'event_ms_per_record' => 'integer',
            'actor_source' => 'string',
            'errors' => 'array',
            'metadata' => 'array',
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
}
