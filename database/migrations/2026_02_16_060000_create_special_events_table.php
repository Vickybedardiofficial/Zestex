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
        Schema::create('special_events', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // "US Election 2024"
            $table->enum('type', ['election', 'war', 'crisis', 'disaster', 'sports', 'other']);
            $table->string('country')->nullable(); // localize impact
            $table->json('keywords')->nullable(); // ["vote", "biden", "trump"]
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->enum('status', ['scheduled', 'active', 'completed'])->default('active');
            $table->decimal('boost_factor', 3, 1)->default(1.0); // 1.5 = 50% more posts
            $table->text('context_prompt')->nullable(); // "Focus on candidate policies..."
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('special_events');
    }
};
