<?php

use BoldlyGrow\AuditLog\AuditLog;
use BoldlyGrow\AuditLog\Models\AuditLog as AuditLogModel;

/**
 * Persist three rows with distinct encrypted values before each test. The
 * lookup index is built from these on first search.
 */
beforeEach(function () {
    AuditLog::create(...auditArgs([
        'record_id' => 'a',
        'attribute_value_old' => 'Active',
        'metadata' => ['approved_by' => 'John Smith', 'ticket' => 'CHG-100'],
        'database' => true,
    ]));

    AuditLog::create(...auditArgs([
        'record_id' => 'b',
        'attribute_value_old' => 'Pending Review',
        'metadata' => ['approved_by' => 'Jane Doe'],
        'database' => true,
    ]));

    AuditLog::create(...auditArgs([
        'record_id' => 'c',
        'attribute_value_old' => 'Inactive',
        'metadata' => ['ticket' => 'CHG-200'],
        'database' => true,
    ]));
});

describe('string column search', function () {
    it('matches an exact value case-insensitively', function () {
        $rows = AuditLogModel::whereEncryptedStringExact('attribute_value_old', 'active')->get();

        expect($rows)->toHaveCount(1)
            ->and($rows->first()->record_id)->toBe('a');
    });

    it('matches a partial value', function () {
        expect(AuditLogModel::whereEncryptedStringPartial('attribute_value_old', 'review')->count())->toBe(1);
    });

    it('matches a starts-with value case-insensitively', function () {
        expect(AuditLogModel::whereEncryptedStringStartsWith('attribute_value_old', 'INACT')->count())->toBe(1);
    });

    it('matches an ends-with value case-insensitively', function () {
        expect(AuditLogModel::whereEncryptedStringEndsWith('attribute_value_old', 'Review')->count())->toBe(1);
    });

    it('returns no results when nothing matches', function () {
        expect(AuditLogModel::whereEncryptedStringExact('attribute_value_old', 'nonexistent')->count())->toBe(0);
    });
});

describe('array column search', function () {
    it('matches an exact value for a specific key', function () {
        $rows = AuditLogModel::whereEncryptedArrayExact('metadata', 'approved_by', 'John Smith')->get();

        expect($rows)->toHaveCount(1)
            ->and($rows->first()->record_id)->toBe('a');
    });

    it('matches a partial value for a specific key', function () {
        expect(AuditLogModel::whereEncryptedArrayPartial('metadata', 'approved_by', 'jane')->count())->toBe(1);
    });

    it('matches a starts-with value for a specific key', function () {
        expect(AuditLogModel::whereEncryptedArrayStartsWith('metadata', 'ticket', 'chg-1')->count())->toBe(1);
    });

    it('matches an ends-with value for a specific key', function () {
        expect(AuditLogModel::whereEncryptedArrayEndsWith('metadata', 'ticket', '200')->count())->toBe(1);
    });

    it('searches across every key and value of the array', function () {
        expect(AuditLogModel::whereEncryptedArraySearch('metadata', 'chg-200')->count())->toBe(1)
            ->and(AuditLogModel::whereEncryptedArraySearch('metadata', 'smith')->count())->toBe(1);
    });
});

describe('array key discovery', function () {
    it('lists the distinct keys present in the encrypted array column', function () {
        expect(AuditLogModel::encryptedArrayKeys('metadata'))
            ->toContain('approved_by')
            ->toContain('ticket');
    });
});

describe('chaining and cache bypass', function () {
    it('chains with ordinary constraints', function () {
        $count = AuditLogModel::whereEncryptedStringPartial('attribute_value_old', 'in')
            ->where('record_id', 'c')
            ->count();

        expect($count)->toBe(1);
    });

    it('rebuilds a fresh index when cache is false', function () {
        expect(AuditLogModel::whereEncryptedStringExact('attribute_value_old', 'active', cache: false)->count())->toBe(1);
    });
});
