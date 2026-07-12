<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Copyable example — Audit Log API routes (Spatie variant)
|--------------------------------------------------------------------------
|
| Merge this group into your application's routes/api.php. Adjust the middleware
| and the `->can()` authorization gate to match your app — `audit-log.view` is a
| placeholder ability you can replace with any permission name you use (a Gate
| ability, a Policy method, or a Spatie permission). See this folder's README
| (Authorization).
|
| Resolved route names: `audit-log.list` and `audit-log.describe`.
| Resolved URIs (under your `/api` prefix): GET /api/audit-logs and
| GET /api/audit-logs/{log}.
|
*/

Route::prefix('audit-logs')
    ->name('audit-log.')
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->controller(AuditLogController::class)
    ->group(function () {
        Route::get('/', 'list')->can('audit-log.view')->name('list');
        Route::get('/{log}', 'describe')->can('audit-log.view')->name('describe');
    });

/*
| Implicit route-model binding (the `{log}` above → `AuditLogModel $log` in the
| controller) excludes soft-deleted rows. To let `describe` return trashed logs
| too, register an explicit binding — for example in your RouteServiceProvider's
| boot() method:
|
| use App\Models\AuditLog;
|
| Route::bind('log', fn ($id) => AuditLog::withTrashed()->findOrFail($id));
*/
