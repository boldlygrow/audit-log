<?php

use BoldlyGrow\AuditLog\AuditLog;
use BoldlyGrow\AuditLog\Exceptions\ImmutableRecordException;
use BoldlyGrow\AuditLog\Models\AuditLog as AuditLogModel;
use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * A stand-in authenticatable actor for exercising actor tracking.
 */
class ImmutabilityActor extends Model implements Authenticatable
{
    use AuthenticatableTrait;

    protected $guarded = [];

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';
}

/**
 * Persist a single audit log record and return the model.
 */
function persistedLog(array $overrides = []): AuditLogModel
{
    AuditLog::create(...auditArgs(array_merge(['record_id' => 'r1', 'database' => true], $overrides)));

    return AuditLogModel::where('event_type', 'test.record.updated.success')->sole();
}

describe('immutable = true (default)', function () {
    it('throws when updating a record', function () {
        config()->set('audit-log.immutable.update', true);
        $log = persistedLog(['message' => 'original']);

        $log->message = 'tampered';

        expect(fn () => $log->save())->toThrow(ImmutableRecordException::class);
        expect(AuditLogModel::whereKey($log->id)->sole()->message)->toBe('original');
    });

    it('throws when force deleting a record', function () {
        config()->set('audit-log.immutable.destroy', true);
        $log = persistedLog();

        expect(fn () => $log->forceDelete())->toThrow(ImmutableRecordException::class);
        expect(AuditLogModel::withTrashed()->whereKey($log->id)->exists())->toBeTrue();
    });

    it('always allows a soft delete and records it', function () {
        $log = persistedLog();

        $log->delete();

        $meta = AuditLogModel::where('event_type', 'audit.log.soft_deleted')->sole();
        expect(AuditLogModel::withTrashed()->whereKey($log->id)->sole()->trashed())->toBeTrue()
            ->and($meta->record_id)->toBe($log->id)
            ->and($meta->record_model)->toBe(AuditLogModel::class)
            ->and($meta->level)->toBe('notice');
    });

    it('records a restore', function () {
        $log = persistedLog();
        $log->delete();

        AuditLogModel::withTrashed()->whereKey($log->id)->sole()->restore();

        expect(AuditLogModel::where('event_type', 'audit.log.restored')->exists())->toBeTrue()
            ->and(AuditLogModel::whereKey($log->id)->exists())->toBeTrue();
    });
});

describe('immutable = false', function () {
    it('allows an update and records it', function () {
        config()->set('audit-log.immutable.update', false);
        $log = persistedLog(['message' => 'original']);

        $log->update(['message' => 'corrected']);

        $meta = AuditLogModel::where('event_type', 'audit.log.updated')->sole();
        expect(AuditLogModel::whereKey($log->id)->sole()->message)->toBe('corrected')
            ->and($meta->record_id)->toBe($log->id);
    });

    it('allows a force delete and records it', function () {
        config()->set('audit-log.immutable.destroy', false);
        $log = persistedLog();
        $id = $log->id;

        $log->forceDelete();

        expect(AuditLogModel::withTrashed()->whereKey($id)->exists())->toBeFalse()
            ->and(AuditLogModel::where('event_type', 'audit.log.destroyed')->sole()->record_id)->toBe($id);
    });
});

describe('mutation metadata', function () {
    it('records an optional justification', function () {
        $log = persistedLog();

        $log->withJustification('Legal hold released, ticket #4567')->delete();

        $meta = AuditLogModel::where('event_type', 'audit.log.soft_deleted')->sole();
        expect($meta->metadata)->toBe(['justification' => 'Legal hold released, ticket #4567']);
    });

    it('rolls back the mutation if recording its audit entry fails', function () {
        $log = persistedLog();

        // Force the meta audit entry to fail as it is being written.
        AuditLogModel::creating(function ($model) {
            if ($model->event_type === 'audit.log.soft_deleted') {
                throw new RuntimeException('recording failed');
            }
        });

        expect(fn () => $log->delete())->toThrow(RuntimeException::class, 'recording failed');

        // The soft delete rolled back together with the failed audit entry: the
        // record is intact and no meta entry was persisted.
        expect(AuditLogModel::count())->toBe(1)
            ->and(AuditLogModel::whereKey($log->id)->sole()->trashed())->toBeFalse()
            ->and(AuditLogModel::where('event_type', 'audit.log.soft_deleted')->exists())->toBeFalse();
    });

    it('tracks the actor who mutated the record', function () {
        $this->actingAs(new ImmutabilityActor([
            'id' => 'actor-9',
            'email' => 'compliance@example.com',
            'name' => 'Compliance Officer',
        ]));

        $log = persistedLog();
        $log->delete();

        $meta = AuditLogModel::where('event_type', 'audit.log.soft_deleted')->sole();
        expect($meta->actor_id)->toBe('actor-9')
            ->and($meta->actor_type)->toBe(ImmutabilityActor::class);
    });
});
