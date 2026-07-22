<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('formation', 10);
            $table->timestamp('locked_at');
            $table->timestamps();
            $table->unique(['league_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('squads');
    }
};
