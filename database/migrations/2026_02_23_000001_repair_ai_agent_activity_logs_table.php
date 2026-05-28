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
        if (!Schema::hasTable('ai_agent_activity_logs')) {
            return;
        }

        Schema::table('ai_agent_activity_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_agent_activity_logs', 'ai_agent_id')) {
                $table->unsignedBigInteger('ai_agent_id')->nullable()->after('id');
            }

            if (!Schema::hasColumn('ai_agent_activity_logs', 'action_type')) {
                $table->string('action_type')->nullable()->after('ai_agent_id');
            }

            if (!Schema::hasColumn('ai_agent_activity_logs', 'action_data')) {
                $table->json('action_data')->nullable()->after('action_type');
            }
        });

        Schema::table('ai_agent_activity_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ai_agent_activity_logs', 'ai_agent_id')) {
                $table->index('ai_agent_id', 'ai_agent_activity_logs_ai_agent_id_idx');
                $table->index(['ai_agent_id', 'action_type'], 'ai_agent_activity_logs_agent_action_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('ai_agent_activity_logs')) {
            return;
        }

        Schema::table('ai_agent_activity_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ai_agent_activity_logs', 'ai_agent_id')) {
                $table->dropIndex('ai_agent_activity_logs_agent_action_idx');
                $table->dropIndex('ai_agent_activity_logs_ai_agent_id_idx');
            }
        });
    }
};

