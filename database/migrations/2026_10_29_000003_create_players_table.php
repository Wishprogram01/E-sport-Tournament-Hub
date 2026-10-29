<?php

use App\Models\Player;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->string('ign');
            $table->enum('role', Player::ROLES);
            $table->boolean('is_starter')->default(true);
            $table->unsignedInteger('kills')->default(0);
            $table->unsignedInteger('deaths')->default(0);
            $table->unsignedInteger('assists')->default(0);
            $table->timestamps();

            $table->unique(['team_id', 'ign']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
