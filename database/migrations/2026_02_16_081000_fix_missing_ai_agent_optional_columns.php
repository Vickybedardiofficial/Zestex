<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_agents')) {
            return;
        }

        Schema::table('ai_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_agents', 'ai_provider')) {
                $table->string('ai_provider')->nullable()->after('engagement_level');
            }

            if (!Schema::hasColumn('ai_agents', 'image_provider')) {
                $table->string('image_provider')->nullable()->after('ai_provider');
            }

            if (!Schema::hasColumn('ai_agents', 'avatar_source')) {
                $table->string('avatar_source')->nullable()->after('image_provider');
            }

            if (!Schema::hasColumn('ai_agents', 'peak_active_hour')) {
                $table->integer('peak_active_hour')->nullable();
            }

            if (!Schema::hasColumn('ai_agents', 'specific_topics')) {
                $table->json('specific_topics')->nullable();
            }

            if (!Schema::hasColumn('ai_agents', 'is_manual_override')) {
                $table->boolean('is_manual_override')->default(false);
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

    public function down(): void
    {
        // no-op safe migration
    }
};
