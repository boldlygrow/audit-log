<?php

use BoldlyGrow\AuditLog\Tests\TestCase;

uses(TestCase::class)->in(__DIR__ . '/Feature');

/**
 * A minimal set of required arguments for AuditLog::create().
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function auditArgs(array $overrides = []): array
{
    return array_merge([
        'event_type' => 'test.record.updated.success',
        'level' => 'info',
        'message' => 'Updated',
        'method' => 'App\\Services\\Sync::run',
        'log' => false,
    ], $overrides);
}
