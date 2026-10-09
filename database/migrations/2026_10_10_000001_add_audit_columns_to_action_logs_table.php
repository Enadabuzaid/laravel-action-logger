<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('action_logs', function (Blueprint $table) {
            $table->string('module', 64)->nullable();
            $table->string('severity', 8)->default('info');
            $table->string('request_id', 64)->nullable();
            $table->index(['module', 'created_at'], 'action_logs_module_created_index');
            $table->index(['actor_type', 'actor_id', 'created_at'], 'action_logs_actor_created_index');
        });

        Schema::table('action_logs', function (Blueprint $table) {
            $table->string('subject_type')->nullable()->change();
            $table->unsignedBigInteger('subject_id')->nullable()->change();
        });
    }

    /**
     * The subject columns are intentionally left nullable on rollback.
     */
    public function down(): void
    {
        Schema::table('action_logs', function (Blueprint $table) {
            $table->dropIndex('action_logs_module_created_index');
            $table->dropIndex('action_logs_actor_created_index');
        });

        Schema::table('action_logs', function (Blueprint $table) {
            $table->dropColumn(['module', 'severity', 'request_id']);
        });
    }
};
