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
        Schema::create($this->table(), function (Blueprint $table) {
            $table->id();

            // Event metadata
            $table->string('event_type')->nullable();
            $table->string('level')->nullable();
            $table->text('message')->nullable();
            $table->string('method')->nullable();
            $table->timestamp('occurred_at')->nullable();

            // Actor (encrypted columns use TEXT to fit the ciphertext)
            $table->text('actor_email')->nullable();
            $table->string('actor_id')->nullable();
            $table->string('actor_ip_addr')->nullable();
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
            $table->string('parent_id')->nullable();
            $table->string('parent_model')->nullable();
            $table->string('parent_type')->nullable();
            $table->string('parent_provider_id')->nullable();
            $table->string('parent_reference_key')->nullable();
            $table->text('parent_reference_value')->nullable(); // encrypted

            // Record (affected model)
            $table->string('record_id')->nullable();
            $table->string('record_model')->nullable();
            $table->string('record_type')->nullable();
            $table->string('record_provider_id')->nullable();
            $table->string('record_reference_key')->nullable();
            $table->text('record_reference_value')->nullable(); // encrypted

            // Related account
            $table->string('related_id')->nullable();
            $table->string('related_model')->nullable();
            $table->string('related_type')->nullable();

            // Subject account
            $table->string('subject_id')->nullable();
            $table->string('subject_model')->nullable();
            $table->string('subject_type')->nullable();

            // Tenant (top-level organization)
            $table->string('tenant_id')->nullable();
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
            $table->index(['record_type', 'record_id']);
            $table->index('actor_id');
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
