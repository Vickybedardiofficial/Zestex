<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Safety guard: never drop an existing production table in this recovery migration.
        if (Schema::hasTable('ai_agents')) {
            return;
        }

        Schema::create('ai_agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            // $table->foreign('user_id')->references('id')->on(Table::USERS)->onDelete('cascade');
            
            // Core Identity
            $table->string('personality_type')->default('general');
            $table->string('country')->default('IN');
            $table->string('language')->default('en');
            $table->string('city')->nullable();
            $table->integer('age')->nullable();
            $table->date('date_of_birth')->nullable();
            
            // Configuration
            $table->json('activity_schedule')->nullable();
            $table->json('topics')->nullable();
            $table->integer('posting_frequency')->default(5);
            $table->integer('engagement_level')->default(3);
            
            // State
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->string('warm_up_stage')->nullable();
            
            // Auto-Creation Metadata
            $table->boolean('auto_created')->default(false);
            $table->timestamp('account_created_at')->nullable();

            // AI Providers
            $table->string('ai_provider')->nullable();
            $table->string('image_provider')->nullable();
            $table->string('avatar_source')->nullable();

            // Limits
            $table->integer('daily_posts_limit')->default(8);
            $table->integer('daily_posts_count')->default(0);
            $table->integer('daily_comments_limit')->default(80);
            $table->integer('daily_comments_count')->default(0);
            $table->integer('daily_likes_limit')->default(100);
            $table->integer('daily_likes_count')->default(0);
            $table->integer('daily_shares_limit')->default(20);
            $table->integer('daily_shares_count')->default(0);
            $table->date('last_limit_reset_date')->nullable();

            // Admin Controls
            $table->integer('peak_active_hour')->nullable();
            $table->json('specific_topics')->nullable();
            $table->boolean('is_manual_override')->default(false);
            $table->json('blocked_topics')->nullable();
            $table->text('manual_instruction')->nullable();
            $table->float('post_frequency_modifier')->default(1.0);

            // Evolution
            $table->integer('reputation_score')->default(50);
            $table->string('evolution_stage')->default('newcomer');
            $table->timestamp('last_reputation_update')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Intentionally left non-destructive.
    }
};
