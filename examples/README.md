# Examples

Copyable reference implementations for common tasks with `boldlygrow/audit-log`. These files are **not**
part of the package's autoloaded code — they use the `App\` namespace and are meant to be copied into your
own Laravel application and adapted.

## API endpoints

Two variants of a read-only `list` + `describe` API over persisted audit log records. Both expose the
human-friendly `*_type` strings (e.g. `okta_user`) rather than the fully-qualified `*_model` class names,
so API/SIEM/CSV/JSON consumers filter on stable, readable values.

| Variant | When to use | Dependencies |
|---------|-------------|--------------|
| [`api-endpoints-no-dependency`](api-endpoints-no-dependency) | You want the simplest possible listing endpoint — the model results, paginated — with no extra packages. | None beyond this package. |
| [`api-endpoints-spatie-data-query`](api-endpoints-spatie-data-query) | You want declarative `filter[...]` query parameters (including encrypted-field search) and a typed response DTO. | `spatie/laravel-query-builder`, `spatie/laravel-data` (optional `dedoc/scramble` for docs). |

Each folder has its own README with install steps, where to copy the files, and the assumptions it makes.

## Shared assumptions

- Both reference an `App\Models\AuditLog` that **extends** `BoldlyGrow\AuditLog\Models\AuditLog` (see the
  package README, "Database Persistence"). You can point them at the base model instead.
- Database persistence is enabled and the migration has run.

## Authorization

Each route applies `->can('audit-log.view')`. That ability name is a **placeholder** — replace it with any
permission your app uses (a Gate ability, a Policy method, or a Spatie permission). The `auth:sanctum` and
`throttle` middleware are placeholders too. Each endpoint is only as protected as the gate and middleware
you configure, so ensure callers are authenticated and authorized before records are returned. Each
folder's README has the details.

## ⚠️ PII

The responses expose columns the package encrypts at rest (actor name/username, before/after attribute
values, metadata), which decrypt transparently on read. Apply your own authorization and redaction before
returning them to a client.
