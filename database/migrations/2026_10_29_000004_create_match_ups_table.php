<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_ups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained('tournaments')->cascadeOnDelete();
            $table->foreignId('team_a_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->foreignId('team_b_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->unsignedTinyInteger('team_a_score')->default(0);
            $table->unsignedTinyInteger('team_b_score')->default(0);
            $table->foreignId('winner_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->unsignedTinyInteger('round_number');
            $table->unsignedTinyInteger('bracket_position');
            $table->unsignedTinyInteger('best_of')->default(3);
            $table->enum('status', ['PENDING', 'LIVE', 'COMPLETED'])->default('PENDING');
            $table->foreignId('next_match_id')->nullable()->constrained('match_ups')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tournament_id', 'round_number', 'bracket_position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_ups');
    }
};
