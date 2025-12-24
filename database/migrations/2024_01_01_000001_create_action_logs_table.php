<?php

declare(strict_types=1);

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
        Schema::create('action_logs', function (Blueprint $table) {
            $table->id();

            // Subject (the model being audited) - polymorphic relation
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');

            // Actor (who performed the action) - polymorphic relation, nullable for system actions
            $table->string('actor_type')->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();

            // Event type (created, updated, deleted, restored, custom events)
            $table->string('event');

            // Values before and after the change
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Additional metadata (custom data, tags, etc.)
            $table->json('meta')->nullable();

            // Request context
            $table->string('ip_address', 45)->nullable(); // IPv6 support
            $table->text('user_agent')->nullable();

            // Multi-tenancy support
            $table->unsignedBigInteger('tenant_id')->nullable();

            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['subject_type', 'subject_id'], 'action_logs_subject_index');
            $table->index(['actor_type', 'actor_id'], 'action_logs_actor_index');
            $table->index('event', 'action_logs_event_index');
            $table->index('tenant_id', 'action_logs_tenant_id_index');
            $table->index('created_at', 'action_logs_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('action_logs');
    }
};
