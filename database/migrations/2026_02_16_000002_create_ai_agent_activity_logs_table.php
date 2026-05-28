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
        if (Schema::hasTable('ai_agent_activity_logs')) {
            return;
        }

        Schema::create('ai_agent_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_agent_id');
            $table->foreign('ai_agent_id')->references('id')->on('ai_agents')->onDelete('cascade');
            
            $table->string('action_type'); // post_created, comment_posted, profile_updated, etc.
            $table->json('action_data')->nullable(); // Additional data about the action
            
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_agent_activity_logs');
    }
};
