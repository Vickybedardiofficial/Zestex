<?php

use App\Database\Configs\Table;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_agents', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
                // $table->foreign('user_id')->references('id')->on(Table::USERS)->onDelete('cascade');
            }
            if (!Schema::hasColumn('ai_agents', 'personality_type')) {
                $table->string('personality_type')->default('general');
            }
            if (!Schema::hasColumn('ai_agents', 'country')) {
                $table->string('country')->default('IN');
            }
            if (!Schema::hasColumn('ai_agents', 'language')) {
                $table->string('language')->default('en');
            }
            if (!Schema::hasColumn('ai_agents', 'activity_schedule')) {
                $table->json('activity_schedule')->nullable();
            }
            if (!Schema::hasColumn('ai_agents', 'topics')) {
                $table->json('topics')->nullable();
            }
            if (!Schema::hasColumn('ai_agents', 'posting_frequency')) {
                $table->integer('posting_frequency')->default(5);
            }
            if (!Schema::hasColumn('ai_agents', 'engagement_level')) {
                $table->integer('engagement_level')->default(3);
            }
            if (!Schema::hasColumn('ai_agents', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('ai_agents', 'last_activity_at')) {
                $table->timestamp('last_activity_at')->nullable();
            }
            // Add missing fields for Auto Creation if they are not there
            if (!Schema::hasColumn('ai_agents', 'auto_created')) {
                $table->boolean('auto_created')->default(false);
            }
            if (!Schema::hasColumn('ai_agents', 'account_created_at')) {
                $table->timestamp('account_created_at')->nullable();
            }
            if (!Schema::hasColumn('ai_agents', 'warm_up_stage')) {
                 $table->string('warm_up_stage')->nullable();
            }
             if (!Schema::hasColumn('ai_agents', 'age')) {
                 $table->integer('age')->nullable();
            }
             if (!Schema::hasColumn('ai_agents', 'city')) {
                 $table->string('city')->nullable();
            }
             if (!Schema::hasColumn('ai_agents', 'date_of_birth')) {
                 $table->date('date_of_birth')->nullable();
            }
        });
    }

    public function down(): void
    {
        // No down needed for recovery
    }
};
