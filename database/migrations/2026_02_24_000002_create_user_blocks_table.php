<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Database\Configs\Table;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(Table::USER_BLOCKS, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('blocker_id');
            $table->unsignedBigInteger('blocked_id');
            $table->timestamps();

            $table->unique(['blocker_id', 'blocked_id']);
            $table->index('blocker_id');
            $table->index('blocked_id');

            $table->foreign('blocker_id')->references('id')->on(Table::USERS)->cascadeOnDelete();
            $table->foreign('blocked_id')->references('id')->on(Table::USERS)->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(Table::USER_BLOCKS);
    }
};
