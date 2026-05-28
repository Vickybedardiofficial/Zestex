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
        if (Schema::hasTable('news_cache')) {
            return;
        }

        Schema::create('news_cache', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50); // google_news, rss, reddit
            $table->string('category', 50); // politics, sports, tech, entertainment
            $table->text('title');
            $table->text('description')->nullable();
            $table->text('url');
            $table->text('image_url')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_trending')->default(false);
            $table->timestamps();

            $table->index('category');
            $table->index('is_trending');
            $table->index('published_at');
            $table->index(['source', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_cache');
    }
};
