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
        if (!Schema::hasTable('ai_agents')) {
            return;
        }

        Schema::table('ai_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_agents', 'peak_active_hour')) {
                $table->integer('peak_active_hour')->nullable()->comment('Admin override for peak activity hour (0-23)');
            }
            if (!Schema::hasColumn('ai_agents', 'specific_topics')) {
                $table->json('specific_topics')->nullable()->comment('Force agent to talk about specific topics');
            }
            if (!Schema::hasColumn('ai_agents', 'is_manual_override')) {
                $table->boolean('is_manual_override')->default(false)->comment('If true, ignores some auto-behaviors');
            }
            if (!Schema::hasColumn('ai_agents', 'blocked_topics')) {
                $table->json('blocked_topics')->nullable();
            }
            if (!Schema::hasColumn('ai_agents', 'manual_instruction')) {
                $table->text('manual_instruction')->nullable();
            }
            if (!Schema::hasColumn('ai_agents', 'post_frequency_modifier')) {
                $table->float('post_frequency_modifier')->default(1.0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn(['peak_active_hour', 'specific_topics', 'is_manual_override', 'blocked_topics', 'manual_instruction', 'post_frequency_modifier']);
        });
    }
};
