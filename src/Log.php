<?php

/**
 * @copyright Jefferson Martin
 * @license MIT <https://spdx.org/licenses/MIT.html>
 *
 * @link https://github.com/boldlygrow/audit-log
 */

namespace BoldlyGrow\AuditLog;

/**
 * Backwards-compatible alias for {@see AuditLog}.
 *
 * This class was renamed from `Log` to `AuditLog` to avoid confusion with
 * Laravel's `Illuminate\Support\Facades\Log`. All methods are static and are
 * inherited from `AuditLog`, so existing `Log::create(...)` calls continue to
 * work unchanged.
 *
 * @deprecated 2.0 Use {@see AuditLog} instead. This alias
 *             is retained for backwards compatibility and will be removed in a
 *             future major version.
 */
class Log extends AuditLog {}
