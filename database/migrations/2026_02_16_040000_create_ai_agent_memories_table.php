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
        Schema::create('ai_agent_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_agent_id')->constrained('ai_agents')->onDelete('cascade');
            $table->string('type')->index(); // short, medium, long
            $table->string('key')->index(); // topic:cricket, user:123
            $table->text('value'); // The actual memory content
            $table->integer('importance')->default(1); // 1-10
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['ai_agent_id', 'type']);
            $table->index(['ai_agent_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_agent_memories');
    }
};
