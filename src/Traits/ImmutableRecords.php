<?php

namespace BoldlyGrow\AuditLog\Traits;

use BoldlyGrow\AuditLog\AuditLog;
use BoldlyGrow\AuditLog\Exceptions\ImmutableRecordException;

/**
 * Enforces the immutability policy in `config('audit-log.immutable')` for
 * persisted audit log records, and records a new audit entry whenever a record is
 * mutated so the trail is self-describing.
 *
 * - An update or a permanent delete (`forceDelete`) throws
 *   {@see ImmutableRecordException} when its config flag is true.
 * - When the flag is false, the operation is allowed and a meta audit entry is
 *   written recording the change and the actor who made it (via the standard
 *   {@see AuditLog::create()} actor resolution).
 * - Soft deletes are always allowed, and every soft delete/restore is recorded.
 *
 * The base model wraps each mutation and the entry it records in a single
 * database transaction (see its `save`/`delete`/`restore` overrides), so a record
 * is never mutated without its log — if either fails, both roll back.
 *
 * Attach an optional justification, stored on the meta entry's `metadata`:
 *
 *   $log->withJustification('Released legal hold, ticket #4567')->delete();
 */
trait ImmutableRecords
{
    /**
     * An optional reason recorded on the next mutation's audit entry.
     */
    public ?string $auditJustification = null;

    /**
     * Attach a justification recorded on the next mutation's audit entry.
     */
    public function withJustification(?string $reason): static
    {
        $this->auditJustification = $reason;

        return $this;
    }

    public static function bootImmutableRecords(): void
    {
        static::updating(function ($model) {
            if ($model->auditLogImmutable('update') && $model->auditLogContentChanges($model->getDirty())) {
                throw ImmutableRecordException::update();
            }
        });

        static::deleting(function ($model) {
            if ($model->isForceDeleting() && $model->auditLogImmutable('destroy')) {
                throw ImmutableRecordException::destroy();
            }
        });

        static::updated(function ($model) {
            // A restore only touches deleted_at and is recorded by the `restored`
            // event below, so skip it here to avoid a misleading "updated" entry.
            if ($model->auditLogContentChanges($model->getChanges())) {
                $model->recordAuditMutation('audit.log.updated', 'notice', 'Audit log record updated');
            }
        });

        static::deleted(function ($model) {
            $model->isForceDeleting()
                ? $model->recordAuditMutation('audit.log.destroyed', 'warning', 'Audit log record permanently deleted')
                : $model->recordAuditMutation('audit.log.soft_deleted', 'notice', 'Audit log record soft deleted');
        });

        static::restored(function ($model) {
            $model->recordAuditMutation('audit.log.restored', 'notice', 'Audit log record restored');
        });
    }

    /**
     * Whether the given `config('audit-log.immutable.*')` control is enabled.
     */
    protected function auditLogImmutable(string $control): bool
    {
        return (bool) config('audit-log.immutable.' . $control);
    }

    /**
     * Whether the given change-set touches any column other than `deleted_at`
     * (i.e. a genuine content change rather than a soft delete/restore).
     *
     * @param  array<string, mixed>  $changes
     */
    protected function auditLogContentChanges(array $changes): bool
    {
        return count(array_diff(array_keys($changes), [$this->getDeletedAtColumn()])) > 0;
    }

    /**
     * Record a new audit entry describing a mutation of this record.
     */
    protected function recordAuditMutation(string $eventType, string $level, string $message): void
    {
        /** @var int|string $key */
        $key = $this->getKey();

        AuditLog::create(
            event_type: $eventType,
            level: $level,
            message: $message,
            method: __METHOD__,
            record_id: (string) $key,
            record_model: static::class,
            metadata: array_filter(['justification' => $this->auditJustification]),
            database: true,
        );

        $this->auditJustification = null;
    }
}
