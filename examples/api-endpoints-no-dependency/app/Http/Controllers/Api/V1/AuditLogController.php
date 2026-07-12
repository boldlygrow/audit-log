<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\AuditLog;

/**
 * Copyable example — a minimal read-only Audit Log API using only
 * Laravel/Eloquent (no external packages).
 *
 * This is intentionally primitive: it returns the audit log models directly,
 * newest first, with pagination. For declarative `filter[...]` query parameters,
 * encrypted-field search, and a typed response DTO, see the sibling
 * `api-endpoints-spatie-data-query` example.
 *
 * `App\Models\AuditLog` must extend `BoldlyGrow\AuditLog\Models\AuditLog`
 * (see the package README, "Database Persistence"). Point it at the base model
 * instead if you have not created your own.
 *
 * ⚠️  PII: audit log rows include columns the package encrypts at rest (actor
 * name/username, before/after attribute values, metadata) which decrypt
 * transparently on read — returning models directly therefore exposes plaintext.
 * Add a response transformer and scope the query to what the requester may see
 * before exposing this to clients. See this folder's README (Authorization).
 */
class AuditLogController
{
    /**
     * List of Audit Logs
     */
    public function list()
    {
        return AuditLog::latest()->paginate();
    }

    /**
     * Describe an Audit Log
     */
    public function describe(AuditLog $log)
    {
        return $log;
    }
}
