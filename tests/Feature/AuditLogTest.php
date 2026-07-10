<?php

use BoldlyGrow\AuditLog\AuditLog;
use BoldlyGrow\AuditLog\Exceptions\ValidationException;
use BoldlyGrow\AuditLog\Models\AuditLog as AuditLogModel;

describe('model to type auto-calculation', function () {
    it('calculates the snake_case type from a model class', function () {
        $result = AuditLog::create(...auditArgs([
            'record_id' => '123',
            'record_model' => 'App\\Models\\Okta\\User',
        ]));

        expect($result['record_type'])->toBe('okta_user');
    });

    it('respects a custom configured model namespace', function () {
        config()->set('audit-log.model.namespace', 'Domain\\Entities\\');

        $result = AuditLog::create(...auditArgs([
            'record_model' => 'Domain\\Entities\\Billing\\Invoice',
        ]));

        expect($result['record_type'])->toBe('billing_invoice');
    });

    it('keeps a legacy *_type string unchanged for backwards compatibility', function () {
        $result = AuditLog::create(...auditArgs([
            'record_type' => 'App\\Models\\Legacy\\Thing',
        ]));

        expect($result['record_type'])->toBe('App\\Models\\Legacy\\Thing');
    });

    it('lets *_model take precedence over a legacy *_type', function () {
        $result = AuditLog::create(...auditArgs([
            'record_type' => 'App\\Models\\Legacy\\Thing',
            'record_model' => 'App\\Models\\Okta\\User',
        ]));

        expect($result['record_type'])->toBe('App\\Models\\Legacy\\Thing');
    });
});

describe('relationship fields', function () {
    it('computes related and subject types', function () {
        $result = AuditLog::create(...auditArgs([
            'related_id' => 'r1',
            'related_model' => 'App\\Models\\Okta\\Group',
            'subject_id' => 's1',
            'subject_model' => 'App\\Models\\Custom\\Widget',
        ]));

        expect($result['related_type'])->toBe('okta_group')
            ->and($result['subject_type'])->toBe('custom_widget');
    });

    it('computes parent and tenant types from their models', function () {
        $result = AuditLog::create(...auditArgs([
            'parent_id' => 'p1',
            'parent_model' => 'App\\Models\\Okta\\Application',
            'tenant_id' => 't1',
            'tenant_model' => 'App\\Models\\Okta\\Organization',
        ]));

        expect($result['parent_type'])->toBe('okta_application')
            ->and($result['tenant_type'])->toBe('okta_organization');
    });

    it('leaves a relationship type null when neither model nor type is given', function () {
        $result = AuditLog::create(...auditArgs());

        expect($result)->toHaveKey('related_type')
            ->and($result['related_type'])->toBeNull()
            ->and($result['subject_type'])->toBeNull();
    });
});

describe('actor source', function () {
    it('resolves to system under the console', function () {
        $result = AuditLog::create(...auditArgs());

        expect($result['actor_source'])->toBe('system')
            ->and(config('audit-log.actor.source.allowed'))->toContain('system');
    });

    it('honors an explicitly passed actor_source', function () {
        $result = AuditLog::create(...auditArgs(['actor_source' => 'cli']));

        expect($result['actor_source'])->toBe('cli');
    });

    it('can be disabled via config', function () {
        config()->set('audit-log.actor.source.enabled', false);

        $result = AuditLog::create(...auditArgs());

        expect($result['actor_source'])->toBeNull();
    });
});

describe('proxy ip header', function () {
    it('reads the actor ip from a configured proxy header', function () {
        config()->set('audit-log.actor.ip_headers', ['CF-Connecting-IP']);
        request()->headers->set('CF-Connecting-IP', '203.0.113.7');

        $result = AuditLog::create(...auditArgs());

        expect($result['actor_ip_addr'])->toBe('203.0.113.7');
    });
});

describe('output tweaks', function () {
    it('renders date fields as Zulu', function () {
        $result = AuditLog::create(...auditArgs([
            'occurred_at' => '2024-03-02T18:51:10+00:00',
        ]));

        expect($result['occurred_at'])->toEndWith('Z');
    });
});

describe('response array shaping', function () {
    it('filters the response to the requested dump_keys', function () {
        $result = AuditLog::create(...auditArgs([
            'record_id' => '123',
            'dump_keys' => ['event_type', 'message', 'record_id'],
        ]));

        expect(array_keys($result))->toEqualCanonicalizing([
            'datetime', 'event_type', 'message', 'record_id',
        ]);
    });
});

describe('side effects', function () {
    it('returns an array without persisting when database is false', function () {
        $result = AuditLog::create(...auditArgs(['database' => false]));

        expect($result)->toBeArray()
            ->and(AuditLogModel::count())->toBe(0);
    });

    it('persists to the database and flattens whitelisted metadata into columns', function () {
        AuditLog::create(...auditArgs([
            'record_id' => '999',
            'record_model' => 'App\\Models\\Okta\\User',
            'metadata' => ['custom_test_field' => 'flattened-value'],
            'database' => true,
        ]));

        $row = AuditLogModel::firstOrFail();

        expect($row->record_id)->toBe('999')
            ->and($row->record_type)->toBe('okta_user')
            ->and($row->actor_source)->toBe('system')
            ->and($row->custom_test_field)->toBe('flattened-value')
            ->and($row->metadata)->toBe(['custom_test_field' => 'flattened-value']);
    });

    it('persists via the deprecated transaction alias', function () {
        AuditLog::create(...auditArgs([
            'record_id' => '42',
            'transaction' => true,
        ]));

        expect(AuditLogModel::count())->toBe(1);
    });

    it('does not persist when database is disabled in config', function () {
        config()->set('audit-log.database.enabled', false);

        AuditLog::create(...auditArgs(['database' => true]));

        expect(AuditLogModel::count())->toBe(0);
    });

    it('skips persistence and warns when the configured model is invalid', function () {
        config()->set('audit-log.database.model', 'App\\Models\\DoesNotExist');

        AuditLog::create(...auditArgs(['database' => true]));

        expect(AuditLogModel::count())->toBe(0);
    });
});

describe('validation', function () {
    it('throws on an invalid log level', function () {
        AuditLog::create(...auditArgs(['level' => 'bogus']));
    })->throws(ValidationException::class);

    it('throws on an actor_source outside the allowed list', function () {
        AuditLog::create(...auditArgs(['actor_source' => 'not-allowed']));
    })->throws(ValidationException::class);
});
