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
            if (!Schema::hasColumn('ai_agents', 'daily_posts_limit')) {
                $table->integer('daily_posts_limit')->default(5)->after('peak_active_hour');
            }
            if (!Schema::hasColumn('ai_agents', 'daily_posts_count')) {
                $table->integer('daily_posts_count')->default(0)->after('daily_posts_limit');
            }
            if (!Schema::hasColumn('ai_agents', 'daily_comments_limit')) {
                $table->integer('daily_comments_limit')->default(30)->after('daily_posts_count');
            }
            if (!Schema::hasColumn('ai_agents', 'daily_comments_count')) {
                $table->integer('daily_comments_count')->default(0)->after('daily_comments_limit');
            }
            if (!Schema::hasColumn('ai_agents', 'daily_likes_limit')) {
                $table->integer('daily_likes_limit')->default(80)->after('daily_comments_count');
            }
            if (!Schema::hasColumn('ai_agents', 'daily_likes_count')) {
                $table->integer('daily_likes_count')->default(0)->after('daily_likes_limit');
            }
            if (!Schema::hasColumn('ai_agents', 'daily_shares_limit')) {
                $table->integer('daily_shares_limit')->default(10)->after('daily_likes_count');
            }
            if (!Schema::hasColumn('ai_agents', 'daily_shares_count')) {
                $table->integer('daily_shares_count')->default(0)->after('daily_shares_limit');
            }
            if (!Schema::hasColumn('ai_agents', 'last_limit_reset_date')) {
                $table->date('last_limit_reset_date')->nullable()->after('daily_shares_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_agents', function (Blueprint $table) {
            $table->dropColumn([
                'daily_posts_limit',
                'daily_posts_count',
                'daily_comments_limit',
                'daily_comments_count',
                'daily_likes_limit',
                'daily_likes_count',
                'daily_shares_limit',
                'daily_shares_count',
                'last_limit_reset_date'
            ]);
        });
    }
};
