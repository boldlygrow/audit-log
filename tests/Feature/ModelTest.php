<?php

use BoldlyGrow\AuditLog\AuditLog;
use BoldlyGrow\AuditLog\Models\AuditLog as AuditLogModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A stand-in related model used to exercise the polymorphic relationships.
 */
class MorphTarget extends Model
{
    protected $table = 'morph_targets';

    protected $guarded = [];

    public $timestamps = false;
}

beforeEach(function () {
    Schema::create('morph_targets', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
    });
});

describe('table name', function () {
    it('resolves the table name from config', function () {
        config()->set('audit-log.database.table', 'my_audit_entries');

        expect((new AuditLogModel)->getTable())->toBe('my_audit_entries');
    });
});

describe('attribute casting', function () {
    it('casts json and datetime columns on retrieval', function () {
        AuditLog::create(...auditArgs([
            'record_id' => '1',
            'occurred_at' => '2024-03-02T18:51:10+00:00',
            'errors' => ['boom'],
            'metadata' => ['k' => 'v'],
            'database' => true,
        ]));

        $log = AuditLogModel::firstOrFail();

        expect($log->errors)->toBe(['boom'])
            ->and($log->metadata)->toBe(['k' => 'v'])
            ->and($log->occurred_at)->toBeInstanceOf(Carbon::class)
            ->and($log->count_records)->toBeNull();
    });
});

describe('polymorphic relationships', function () {
    it('resolves the record relationship via the record_model column', function () {
        $target = MorphTarget::create(['name' => 'widget']);

        AuditLog::create(...auditArgs([
            'record_id' => (string) $target->id,
            'record_model' => MorphTarget::class,
            'database' => true,
        ]));

        $log = AuditLogModel::firstOrFail();

        expect($log->record)->toBeInstanceOf(MorphTarget::class)
            ->and($log->record->id)->toBe($target->id)
            ->and($log->record_type)->toBe('morph_target');
    });

    it('resolves the tenant relationship via the tenant_model column', function () {
        $target = MorphTarget::create(['name' => 'acme']);

        AuditLog::create(...auditArgs([
            'tenant_id' => (string) $target->id,
            'tenant_model' => MorphTarget::class,
            'database' => true,
        ]));

        $log = AuditLogModel::firstOrFail();

        expect($log->tenant)->toBeInstanceOf(MorphTarget::class)
            ->and($log->tenant->id)->toBe($target->id)
            ->and($log->tenant_type)->toBe('morph_target');
    });

    it('returns null for a relationship with no model reference', function () {
        AuditLog::create(...auditArgs(['record_id' => '1', 'database' => true]));

        $log = AuditLogModel::firstOrFail();

        expect($log->subject)->toBeNull();
    });
});

describe('encryption at rest', function () {
    it('stores sensitive fields as ciphertext but decrypts them via the model', function () {
        AuditLog::create(...auditArgs([
            'record_id' => '1',
            'attribute_key' => 'status',
            'attribute_value_old' => 'active',
            'attribute_value_new' => 'suspended',
            'metadata' => ['ssn' => '123-45-6789'],
            'database' => true,
        ]));

        // The model transparently decrypts the values.
        $log = AuditLogModel::firstOrFail();
        expect($log->attribute_value_old)->toBe('active')
            ->and($log->attribute_value_new)->toBe('suspended')
            ->and($log->metadata)->toBe(['ssn' => '123-45-6789']);

        // The raw column values are encrypted, not the plaintext.
        $raw = DB::table('audit_logs')->first();
        expect($raw->attribute_value_old)->not->toBe('active')
            ->and($raw->attribute_value_new)->not->toBe('suspended')
            ->and($raw->metadata)->not->toContain('123-45-6789')
            ->and($raw->attribute_key)->toBe('status'); // non-encrypted column stays plaintext
    });
});

describe('soft deletes', function () {
    it('soft deletes rows', function () {
        AuditLog::create(...auditArgs(['record_id' => '1', 'database' => true]));
        $log = AuditLogModel::firstOrFail();

        $log->delete();

        // The specific record is soft deleted (a separate meta-log entry is also
        // recorded — see ImmutabilityTest).
        expect(AuditLogModel::whereKey($log->id)->exists())->toBeFalse()
            ->and(AuditLogModel::withTrashed()->whereKey($log->id)->first()->trashed())->toBeTrue();
    });
});

describe('persisted columns', function () {
    it('persists the standardized schema columns', function () {
        AuditLog::create(...auditArgs([
            'record_id' => '999',
            'record_model' => 'App\\Models\\Okta\\User',
            'job_id' => 'job-1',
            'job_platform' => 'redis',
            'occurred_at' => '2024-03-02T18:51:10+00:00',
            'attribute_key' => 'status',
            'attribute_value_old' => 'active',
            'attribute_value_new' => 'suspended',
            'count_records' => 5,
            'database' => true,
        ]));

        $log = AuditLogModel::firstOrFail();

        expect($log->event_type)->toBe('test.record.updated.success')
            ->and($log->method)->toBe('App\\Services\\Sync::run')
            ->and($log->record_model)->toBe('App\\Models\\Okta\\User')
            ->and($log->record_type)->toBe('okta_user')
            ->and($log->job_id)->toBe('job-1')
            ->and($log->job_platform)->toBe('redis')
            ->and($log->attribute_key)->toBe('status')
            ->and($log->count_records)->toBe(5)
            ->and($log->actor_source)->toBe('system')
            ->and($log->occurred_at->format('Y-m-d'))->toBe('2024-03-02');
    });
});

describe('primary key', function () {
    it('generates a UUIDv7 string primary key', function () {
        AuditLog::create(...auditArgs(['record_id' => '1', 'database' => true]));

        $log = AuditLogModel::firstOrFail();

        // A canonical UUID with version nibble 7.
        expect($log->getKeyName())->toBe('id')
            ->and($log->getKeyType())->toBe('string')
            ->and($log->getIncrementing())->toBeFalse()
            ->and($log->id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/');
    });
});
