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
            // Warm-up tracking
            $table->timestamp('account_created_at')->nullable();
            $table->enum('warm_up_stage', ['day1', 'day2', 'day3', 'active'])->default('day1');
            $table->timestamp('warm_up_completed_at')->nullable();
            
            // Auto-creation flag
            $table->boolean('auto_created')->default(true);
            
            // Additional identity fields
            $table->integer('age')->nullable();
            $table->string('city')->nullable();
            $table->date('date_of_birth')->nullable();
            
            // Warm-up activity counters
            $table->integer('warm_up_likes_today')->default(0);
            $table->integer('warm_up_shares_today')->default(0);
            $table->integer('warm_up_comments_today')->default(0);
            $table->date('warm_up_activity_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn([
                'account_created_at',
                'warm_up_stage',
                'warm_up_completed_at',
                'auto_created',
                'age',
                'city',
                'date_of_birth',
                'warm_up_likes_today',
                'warm_up_shares_today',
                'warm_up_comments_today',
                'warm_up_activity_date',
            ]);
        });
    }
};
