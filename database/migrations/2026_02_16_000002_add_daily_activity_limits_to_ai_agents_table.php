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
            // Daily activity limits
            $table->integer('daily_posts_limit')->default(5);
            $table->integer('daily_posts_count')->default(0);
            
            $table->integer('daily_comments_limit')->default(30);
            $table->integer('daily_comments_count')->default(0);
            
            $table->integer('daily_likes_limit')->default(100);
            $table->integer('daily_likes_count')->default(0);
            
            $table->integer('daily_shares_limit')->default(10);
            $table->integer('daily_shares_count')->default(0);
            
            $table->date('last_limit_reset_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn([
                'daily_posts_limit', 'daily_posts_count',
                'daily_comments_limit', 'daily_comments_count',
                'daily_likes_limit', 'daily_likes_count',
                'daily_shares_limit', 'daily_shares_count',
                'last_limit_reset_date',
            ]);
        });
    }
};
