<?php

use BoldlyGrow\AuditLog\AuditLog;
use Illuminate\Support\Facades\Log;

describe('system log entry', function () {
    it('writes a log with the class-only message prefix', function () {
        Log::spy();

        AuditLog::create(...auditArgs([
            'log' => true,
            'message' => 'Updated',
            'method' => 'App\\Services\\Okta\\Sync::run',
        ]));

        Log::shouldHaveReceived('log')->once()->withArgs(
            fn ($level, $message, $context) => $level === 'info'
                && $message === 'Sync Updated'
                && $context['event_type'] === 'test.record.updated.success'
                && $context['method'] === 'App\\Services\\Okta\\Sync::run'
                && array_key_exists('memory_current', $context)
                && array_key_exists('memory_peak', $context)
        );
    });

    it('does not write a log when log is false', function () {
        Log::spy();

        AuditLog::create(...auditArgs(['log' => false]));

        Log::shouldNotHaveReceived('log');
    });

    it('logs at the requested level', function () {
        Log::spy();

        AuditLog::create(...auditArgs(['log' => true, 'level' => 'warning']));

        Log::shouldHaveReceived('log')->once()->withArgs(
            fn ($level, $message, $context) => $level === 'warning'
        );
    });
});

describe('database persistence warnings', function () {
    it('warns and skips persistence when the audit table does not exist', function () {
        config()->set('audit-log.database.table', 'missing_audit_table');
        Log::spy();

        AuditLog::create(...auditArgs(['database' => true]));

        Log::shouldHaveReceived('log')->withArgs(
            fn ($level, $message, $context = []) => $level === 'warning'
                && str_contains($message, 'Audit Logs database table not found')
        );
    });
});
