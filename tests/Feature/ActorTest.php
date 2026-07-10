<?php

use BoldlyGrow\AuditLog\AuditLog;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * A lightweight authenticatable actor. It is never persisted — actingAs() only
 * needs the attributes the audit logger reads.
 */
class TestActor extends Authenticatable
{
    protected $guarded = [];

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';
}

describe('actor metadata', function () {
    it('captures the authenticated user attributes', function () {
        $this->actingAs(new TestActor([
            'id' => 'usr_01H8',
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
            'username' => 'jane',
            'provider_id' => 'okta-123',
        ]));

        $result = AuditLog::create(...auditArgs());

        expect($result['actor_id'])->toBe('usr_01H8')
            ->and($result['actor_email'])->toBe('jane@example.com')
            ->and($result['actor_name'])->toBe('Jane Doe')
            ->and($result['actor_username'])->toBe('jane')
            ->and($result['actor_provider_id'])->toBe('okta-123')
            ->and($result['actor_type'])->toBe(TestActor::class);
    });

    it('falls back to full_name when name is absent', function () {
        $this->actingAs(new TestActor(['id' => 1, 'full_name' => 'Ada Lovelace']));

        $result = AuditLog::create(...auditArgs());

        expect($result['actor_name'])->toBe('Ada Lovelace');
    });

    it('lets an explicitly passed actor field override the resolved value', function () {
        $this->actingAs(new TestActor(['id' => 7, 'email' => 'jane@example.com']));

        $result = AuditLog::create(...auditArgs(['actor_email' => 'override@example.com']));

        expect($result['actor_email'])->toBe('override@example.com');
    });

    it('nulls all actor fields when actor capture is disabled', function () {
        config()->set('audit-log.actor.enabled', false);

        $this->actingAs(new TestActor(['id' => 7, 'email' => 'jane@example.com']));

        $result = AuditLog::create(...auditArgs());

        expect($result['actor_id'])->toBeNull()
            ->and($result['actor_email'])->toBeNull()
            ->and($result['actor_type'])->toBeNull()
            ->and($result['actor_source'])->toBeNull();
    });

    it('leaves actor identity null but still records source when unauthenticated', function () {
        $result = AuditLog::create(...auditArgs());

        expect($result['actor_id'])->toBeNull()
            ->and($result['actor_email'])->toBeNull()
            ->and($result['actor_type'])->toBeNull()
            ->and($result['actor_source'])->toBe('system');
    });
});

describe('custom actor attribute mapping', function () {
    it('reads actor fields from configured user attributes', function () {
        config()->set('audit-log.actor.attributes', [
            'id' => 'uuid',
            'email' => 'work_email',
            'name' => 'display_name',
            'provider_id' => 'okta_id',
            'username' => 'handle',
        ]);

        $this->actingAs(new TestActor([
            'uuid' => 'usr_99',
            'work_email' => 'jane@work.example',
            'display_name' => 'Jane D.',
            'okta_id' => 'okta-999',
            'handle' => 'jd',
        ]));

        $result = AuditLog::create(...auditArgs());

        expect($result['actor_id'])->toBe('usr_99')
            ->and($result['actor_email'])->toBe('jane@work.example')
            ->and($result['actor_name'])->toBe('Jane D.')
            ->and($result['actor_provider_id'])->toBe('okta-999')
            ->and($result['actor_username'])->toBe('jd');
    });

    it('uses the first non-null candidate in an ordered mapping', function () {
        config()->set('audit-log.actor.attributes.name', ['display_name', 'name', 'full_name']);

        $this->actingAs(new TestActor(['id' => 'u1', 'full_name' => 'Grace Hopper']));

        $result = AuditLog::create(...auditArgs());

        expect($result['actor_name'])->toBe('Grace Hopper');
    });
});
