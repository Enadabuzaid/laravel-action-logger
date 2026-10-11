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
            $table->string('subject_label', 190)->nullable();
            $table->string('actor_label', 190)->nullable();
            $table->index(['subject_type', 'subject_id', 'created_at'], 'action_logs_subject_created_index');
        });
    }

    public function down(): void
    {
        Schema::table('action_logs', function (Blueprint $table) {
            $table->dropIndex('action_logs_subject_created_index');
        });

        Schema::table('action_logs', function (Blueprint $table) {
            $table->dropColumn(['subject_label', 'actor_label']);
        });
    }
};
