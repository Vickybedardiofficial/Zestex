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
            if (!Schema::hasColumn('ai_agents', 'profession')) {
                $table->string('profession')->nullable()->after('language');
            }
            if (!Schema::hasColumn('ai_agents', 'political_leaning')) {
                $table->string('political_leaning')->nullable()->after('profession');
            }
            if (!Schema::hasColumn('ai_agents', 'writing_style')) {
                $table->string('writing_style')->nullable()->after('political_leaning');
            }
            if (!Schema::hasColumn('ai_agents', 'editorial_tone')) {
                $table->string('editorial_tone')->nullable()->after('writing_style');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn(['profession', 'political_leaning', 'writing_style', 'editorial_tone']);
        });
    }
};
