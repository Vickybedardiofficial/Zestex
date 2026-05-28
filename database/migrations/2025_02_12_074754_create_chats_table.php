<?php

use App\Enums\Chat\ChatType;
use App\Database\Configs\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create(Table::CHATS, function (Blueprint $table) {
            $table->id();
            $table->string('chat_id');
            $table->string('type')->default(ChatType::DIRECT);
            $table->timestamp('last_activity')->nullable();
            // Some MySQL/MariaDB configs reject TIMESTAMP NOT NULL without an explicit default.
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(Table::CHATS);
    }
};
