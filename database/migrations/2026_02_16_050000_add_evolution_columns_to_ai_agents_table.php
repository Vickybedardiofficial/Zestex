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
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->string('evolution_stage')->default('newcomer')->after('is_active'); // newcomer, rising, established, influencer
            $table->integer('reputation_score')->default(0)->after('evolution_stage');
            $table->timestamp('last_reputation_update')->nullable()->after('reputation_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn(['evolution_stage', 'reputation_score', 'last_reputation_update']);
        });
    }
};
