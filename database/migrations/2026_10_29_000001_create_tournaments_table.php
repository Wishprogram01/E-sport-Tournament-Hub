<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('game_title')->default('Mobile Legends: Bang Bang');
            $table->decimal('prize_pool', 12, 2)->default(0);
            $table->string('format')->default('Single Elimination');
            $table->enum('status', ['REGISTRATION', 'LIVE', 'COMPLETED'])->default('REGISTRATION');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournaments');
    }
};
