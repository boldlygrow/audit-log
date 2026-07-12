# Example: Audit Log API (Spatie Query Builder + Laravel Data)

A copyable, read-only `list` + `describe` API for audit log records, built with
[Spatie Query Builder](https://spatie.be/docs/laravel-query-builder) for declarative filtering and
[Laravel Data](https://spatie.be/docs/laravel-data) for the typed response.

These are **reference stubs**, not autoloaded package code — copy them into your application and adapt.

## What you get

- **Rich, declarative filtering** via `filter[...]` query parameters (exact, partial `_search`,
  encrypted-field, and date-range filters).
- A typed, self-documenting response DTO that exposes the human-friendly `*_type` strings (e.g.
  `okta_user`) instead of the fully-qualified `*_model` class names.
- The package's own logger recording each API query (`audit.log.list.success` /
  `audit.log.describe.success`).

## Install the dependencies

```bash
composer require spatie/laravel-data spatie/laravel-query-builder
```

Tested against `spatie/laravel-data:^4` and `spatie/laravel-query-builder:^6`.

The controller uses [Scramble](https://scramble.dedoc.co/) attributes to generate OpenAPI docs. This is
**optional** — install it only if you want generated API docs:

```bash
composer require dedoc/scramble   # optional
```

All Scramble attributes (and their `use` imports) are left **commented out** in the controller, so the
example has zero Scramble dependency by default — uncomment them to opt in. A couple of notes:

- **`#[QueryParameter]` and `#[PathParameter]` are Scramble Pro (paid) features**, while **`#[Group]`** is
  free. A paid subscription is *not* required to use this example; the annotations are there for you to see
  the benefit and enable if you subscribe.
- Alternatively, Scramble can document the `spatie/laravel-query-builder` filters **automatically** —
  reading the controller's `allowedFilters()` without any per-parameter attributes. See
  [Scramble's Laravel Query Builder support](https://scramble.dedoc.co/packages/laravel-query-builder).

## Copy the files

| Stub | Destination in your app |
|------|-------------------------|
| `app/Data/V1/AuditLog/AuditLogDetailedResponseData.php` | same path |
| `app/Http/Controllers/Api/V1/AuditLogController.php` | same path |
| `routes/api.php` | merge the route group into your `routes/api.php` |

## Assumptions

- You have an `App\Models\AuditLog` that **extends** `BoldlyGrow\AuditLog\Models\AuditLog` (see the
  package README, "Database Persistence"). The controller aliases it as `AuditLogModel` to avoid clashing
  with the package's `BoldlyGrow\AuditLog\AuditLog` logger. To use the base model directly, update the
  `use` statement.
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

The response exposes columns the package encrypts at rest (`actor_name`, `actor_username`,
`attribute_value_old`, `attribute_value_new`, `metadata`). The model decrypts them transparently on read,
so this endpoint returns **plaintext**. Apply your own authorization and redaction before exposing these
to a client, and scope the query to what the requester is allowed to see.

## Indexing note

The shipped migration indexes `id`, `event_type`, `actor_id`, every `(*_type, *_id)` pair (`actor`,
`record`, `parent`, `related`, `subject`, `tenant`), and every `(*_model, *_id)` pair — so the type-based
filters (and morph lookups) are index-backed out of the box, including a bare `*_type` filter via each
compound's leftmost prefix. The `*_search` (partial `LIKE`) and encrypted callbacks still scan; add your
own indexes for any you use at scale. The `*_type` columns are plain strings, which is what makes them
convenient for SIEM/export filtering (see the package README, "Simple String Searches with `*_type`").
