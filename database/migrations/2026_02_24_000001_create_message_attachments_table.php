<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Database\Configs\Table;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Table::MESSAGE_ATTACHMENTS, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('type', 20);
            $table->string('disk', 50);
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('original_name')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('message_id');
            $table->index('user_id');
            $table->index('type');

            $table->foreign('message_id')->references('id')->on(Table::MESSAGES)->nullOnDelete();
            $table->foreign('user_id')->references('id')->on(Table::USERS)->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::MESSAGE_ATTACHMENTS);
    }
};
