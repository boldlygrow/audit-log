# Example: Audit Log API (no external dependencies)

A copyable, read-only `list` + `describe` API for audit log records using only Laravel/Eloquent — no
Composer packages beyond this one.

These are **reference stubs**, not autoloaded package code — copy them into your application and adapt.

## What you get

- A `list` endpoint that returns audit log records newest-first, paginated.
- A `describe` endpoint that returns a single record via route-model binding.

It is intentionally **primitive** — just the model results. If you want declarative `filter[...]` query
parameters, encrypted-field search, and a typed response DTO, see the sibling
[`api-endpoints-spatie-data-query`](../api-endpoints-spatie-data-query) example.

## Copy the files

| Stub | Destination in your app |
|------|-------------------------|
| `app/Http/Controllers/Api/V1/AuditLogController.php` | same path |
| `routes/api.php` | merge the route group into your `routes/api.php` |

## Assumptions

- You have an `App\Models\AuditLog` that **extends** `BoldlyGrow\AuditLog\Models\AuditLog` (see the
  package README, "Database Persistence"). To use the base model directly, update the `use` statement.
- Database persistence is enabled and the migration has run, so there are rows to query.

## Authorization

The route applies `->can('audit-log.view')`. **`audit-log.view` is a placeholder** — replace it with any
permission name your app uses. It can be a [Gate ability](https://laravel.com/docs/authorization#gates),
a [Policy](https://laravel.com/docs/authorization#creating-policies) method, or a
[Spatie permission](https://spatie.be/docs/laravel-permission) — anything you register with the framework's
authorization layer. Remove the `->can(...)` call entirely if the endpoint should be public (rarely
appropriate for audit data).

The `auth:sanctum` and `throttle` middleware on the route group are likewise placeholders — swap them for
your app's auth guard and rate limits. This endpoint is only as protected as the gate and middleware you
configure, so make sure a caller must be authenticated and authorized before any records are returned.

## ⚠️ PII

Audit log rows include columns the package encrypts at rest (`actor_name`, `actor_username`,
`attribute_value_old`, `attribute_value_new`, `metadata`). The model decrypts them transparently on read,
so returning models directly — as this primitive example does — exposes **plaintext**. Before using this
in production, add a response transformer (see the Spatie variant's data class for a field-by-field
example) and scope the query to only the records the requester is allowed to see.
