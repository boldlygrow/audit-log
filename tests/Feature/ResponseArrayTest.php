<?php

use BoldlyGrow\AuditLog\AuditLog;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

describe('response array shaping', function () {
    it('applies a named dump_config schema from config', function () {
        config()->set('audit-log.dump.okta_user', [
            'date' => 'Y-m-d',
            'strings' => ['source' => 'okta'],
            'keys' => ['event_type', 'record_id'],
        ]);

        $result = AuditLog::create(...auditArgs([
            'record_id' => 'abc',
            'dump_config' => 'okta_user',
        ]));

        expect(array_keys($result))->toEqualCanonicalizing(['datetime', 'event_type', 'record_id', 'source'])
            ->and($result['source'])->toBe('okta')
            ->and($result['record_id'])->toBe('abc');
    });

    it('appends dump_strings to the response', function () {
        $result = AuditLog::create(...auditArgs([
            'dump_keys' => ['event_type'],
            'dump_strings' => ['custom_key' => 'my_value'],
        ]));

        expect($result['custom_key'])->toBe('my_value');
    });

    it('formats the datetime with the requested dump_date', function () {
        $result = AuditLog::create(...auditArgs([
            'occurred_at' => '2024-03-02T18:51:10+00:00',
            'dump_date' => 'Y-m-d',
        ]));

        expect($result['datetime'])->toBe('2024-03-02');
    });

    it('converts a Carbon ms timestamp to an integer duration', function () {
        $result = AuditLog::create(...auditArgs([
            'event_ms' => Carbon::now()->subMilliseconds(250),
        ]));

        expect($result['event_ms'])->toBeInt()
            ->and($result['event_ms'])->toBeGreaterThanOrEqual(200);
    });

    it('accepts a CarbonImmutable ms timestamp', function () {
        $result = AuditLog::create(...auditArgs([
            'duration_ms' => CarbonImmutable::now()->subMilliseconds(500),
            'event_ms' => CarbonImmutable::now()->subMilliseconds(250),
        ]));

        expect($result['event_ms'])->toBeInt()
            ->and($result['event_ms'])->toBeGreaterThanOrEqual(200)
            ->and($result['duration_ms'])->toBeInt()
            ->and($result['duration_ms'])->toBeGreaterThanOrEqual(450);
    });

    it('includes errors and metadata arrays in the response', function () {
        $result = AuditLog::create(...auditArgs([
            'errors' => ['Something failed'],
            'metadata' => ['uri' => 'users'],
        ]));

        expect($result['errors'])->toBe(['Something failed'])
            ->and($result['metadata'])->toBe(['uri' => 'users']);
    });

    it('returns the full schema when no dump filter is provided', function () {
        $result = AuditLog::create(...auditArgs());

        expect($result)->toHaveKeys([
            'datetime', 'event_type', 'level', 'message', 'method',
            'actor_source', 'record_type', 'related_type', 'subject_type',
        ]);
    });
});
