<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_agent_activity_logs')) {
            return;
        }

        Schema::table('ai_agent_activity_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_agent_activity_logs', 'ai_agent_id')) {
                $table->unsignedBigInteger('ai_agent_id')->nullable()->after('id');
                $table->index('ai_agent_id');
            }

            if (!Schema::hasColumn('ai_agent_activity_logs', 'action_type')) {
                $table->string('action_type')->nullable()->after('ai_agent_id');
                $table->index('action_type');
            }

            if (!Schema::hasColumn('ai_agent_activity_logs', 'action_data')) {
                $table->json('action_data')->nullable()->after('action_type');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_agent_activity_logs')) {
            return;
        }

        Schema::table('ai_agent_activity_logs', function (Blueprint $table) {
            if (Schema::hasColumn('ai_agent_activity_logs', 'action_data')) {
                $table->dropColumn('action_data');
            }

            if (Schema::hasColumn('ai_agent_activity_logs', 'action_type')) {
                $table->dropIndex(['action_type']);
                $table->dropColumn('action_type');
            }

            if (Schema::hasColumn('ai_agent_activity_logs', 'ai_agent_id')) {
                $table->dropIndex(['ai_agent_id']);
                $table->dropColumn('ai_agent_id');
            }
        });
    }
};
