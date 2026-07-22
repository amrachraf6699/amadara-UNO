<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squad_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('squad_id')->constrained()->cascadeOnDelete();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('football_player_id')->constrained()->restrictOnDelete();
            $table->string('slot_key', 30);
            $table->string('role', 10);
            $table->timestamps();
            $table->unique(['squad_id', 'slot_key']);
            $table->unique(['league_id', 'football_player_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squad_selections');
    }
};
