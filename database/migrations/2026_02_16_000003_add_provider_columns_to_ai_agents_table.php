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
            if (!Schema::hasColumn('ai_agents', 'ai_provider')) {
                $table->string('ai_provider')->nullable()->after('engagement_level')->comment('Preferred AI provider (xai, gemini, chatgpt, groq, openrouter, aimlapi)');
            }
            if (!Schema::hasColumn('ai_agents', 'image_provider')) {
                $table->string('image_provider')->nullable()->after('ai_provider')->comment('Preferred image provider (pexels, unsplash, pixabay, ai_generated)');
            }
            if (!Schema::hasColumn('ai_agents', 'avatar_source')) {
                $table->string('avatar_source')->nullable()->after('image_provider')->comment('URL or source of avatar image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn(['ai_provider', 'image_provider', 'avatar_source']);
        });
    }
};
