<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ┌───────────────────────────────────────────────────────────────────┐
        // │ SAFETY CHECK — DELETE THIS BLOCK TO MIGRATE.                        │
        // │                                                                    │
        // │ Review the identifier column formats below — the UUIDv7 primary    │
        // │ key and the CHAR(36) `*_id` columns — and confirm they match your  │
        // │ application's models. Changing an id format later means an extra   │
        // │ migration, so make the decision now. Deleting these lines is your  │
        // │ confirmation. (The guard is skipped under the test runner so it    │
        // │ does not interfere with package or application test suites.)       │
        // └───────────────────────────────────────────────────────────────────┘
        if (! app()->runningUnitTests()) {
            throw new RuntimeException(
                'audit-log: review the identifier column formats in '
                . basename(__FILE__) . ' (UUIDv7 primary key and CHAR(36) *_id columns), '
                . 'then delete the SAFETY CHECK block to run this migration.'
            );
        }

        Schema::create($this->table(), function (Blueprint $table) {
            // Identifier formats — decide before running `php artisan migrate`.
            // -----------------------------------------------------------------
            // `id` (primary key) defaults to UUIDv7: timestamp-ordered and the de
            // facto standard identifier for logging/audit systems. The base model
            // generates it in its `creating` hook. The polymorphic reference ids
            // (`actor_id`, `parent_id`, `record_id`, `related_id`, `subject_id`,
            // `tenant_id`) hold YOUR models' primary keys and default to CHAR(36)
            // to fit a UUID or ULID. Change any of these to match your app (and
            // update the model's `boot()` id generation if you change the primary
            // key). Alternatives:
            //   $table->id();                             // BIGINT auto-increment
            //   $table->ulid('id')->primary();            // ULID (CHAR(26))
            //   $table->unsignedBigInteger('record_id');  // BIGINT model keys
            //   $table->string('record_id');              // custom / mixed / > 36 chars
            // The `*_provider_id`, `*_reference_*`, `actor_session_id`, and job id
            // columns stay VARCHAR — they hold arbitrary external identifiers, not
            // model keys.
            $table->uuid('id')->primary();

            // Event metadata
            $table->string('event_type')->nullable();
            $table->string('level')->nullable();
            $table->text('message')->nullable();
            $table->string('method')->nullable();
            $table->timestamp('occurred_at')->nullable();

            // Actor (encrypted columns use TEXT to fit the ciphertext)
            $table->text('actor_email')->nullable();
            $table->char('actor_id', 36)->nullable(); // model-reference id (see "Identifier formats")
            $table->string('actor_ip_addr')->nullable();
            $table->string('actor_model')->nullable();
            $table->text('actor_name')->nullable();
            $table->string('actor_provider_id')->nullable();
            $table->string('actor_session_id')->nullable();
            $table->string('actor_source')->nullable();
            $table->string('actor_type')->nullable();
            $table->text('actor_username')->nullable();

            // State changes
            $table->string('attribute_key')->nullable();
            $table->text('attribute_value_old')->nullable();
            $table->text('attribute_value_new')->nullable();

            // Counts and durations
            $table->unsignedInteger('count_records')->nullable();
            $table->unsignedInteger('duration_ms_per_record')->nullable();
            $table->unsignedInteger('event_ms_per_record')->nullable();

            // Parent (many-to-many relationship events)
            $table->char('parent_id', 36)->nullable(); // model-reference id (see "Identifier formats")
            $table->string('parent_model')->nullable();
            $table->string('parent_type')->nullable();
            $table->string('parent_provider_id')->nullable();
            $table->string('parent_reference_key')->nullable();
            $table->text('parent_reference_value')->nullable(); // encrypted

            // Record (affected model)
            $table->char('record_id', 36)->nullable(); // model-reference id (see "Identifier formats")
            $table->string('record_model')->nullable();
            $table->string('record_type')->nullable();
            $table->string('record_provider_id')->nullable();
            $table->string('record_reference_key')->nullable();
            $table->text('record_reference_value')->nullable(); // encrypted

            // Related account
            $table->char('related_id', 36)->nullable(); // model-reference id (see "Identifier formats")
            $table->string('related_model')->nullable();
            $table->string('related_type')->nullable();

            // Subject account
            $table->char('subject_id', 36)->nullable(); // model-reference id (see "Identifier formats")
            $table->string('subject_model')->nullable();
            $table->string('subject_type')->nullable();

            // Tenant (top-level organization)
            $table->char('tenant_id', 36)->nullable(); // model-reference id (see "Identifier formats")
            $table->string('tenant_model')->nullable();
            $table->string('tenant_type')->nullable();

            // Background job metadata
            $table->string('job_batch')->nullable();
            $table->string('job_id')->nullable();
            $table->string('job_platform')->nullable();
            $table->string('job_pipeline_id')->nullable();
            $table->timestamp('job_timestamp')->nullable();
            $table->string('job_transaction_id')->nullable();

            // Freeform payloads
            $table->json('errors')->nullable();
            $table->text('metadata')->nullable(); // encrypted:array (ciphertext, not JSON)

            $table->timestamps();
            $table->softDeletes();

            $table->index('event_type');

            // Actor: a standalone `actor_id` for "all events by this account",
            // `(actor_type, actor_id)` for filtering by the snake_case actor class
            // (the compound's leftmost prefix serves `actor_type`), and
            // `(actor_model, actor_id)` for `morphTo`/`whereMorphedTo()` resolution.
            $table->index('actor_id');
            $table->index(['actor_type', 'actor_id']);
            $table->index(['actor_model', 'actor_id']);

            // The snake_case `*_type` columns are indexed with their `*_id` so they
            // can be filtered efficiently as plain strings (API queries, exports,
            // SIEM lookups) — both "history of a specific record" (`type` + `id`)
            // and bare `*_type` filtering via the compound's leftmost prefix — with
            // no need for the fully-qualified `*_model` class name.
            $table->index(['record_type', 'record_id']);
            $table->index(['parent_type', 'parent_id']);
            $table->index(['related_type', 'related_id']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['tenant_type', 'tenant_id']);

            // The `*_model` (fully-qualified class name) columns are indexed with
            // their `*_id` because the Eloquent `morphTo` relationships and
            // `whereMorphedTo()` resolve on `*_model` + `*_id`.
            $table->index(['record_model', 'record_id']);
            $table->index(['parent_model', 'parent_id']);
            $table->index(['related_model', 'related_id']);
            $table->index(['subject_model', 'subject_id']);
            $table->index(['tenant_model', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->table());
    }

    /**
     * The configured audit log table name.
     */
    private function table(): string
    {
        return (string) config('audit-log.database.table', 'audit_logs');
    }
};
