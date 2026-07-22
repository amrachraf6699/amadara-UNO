<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('football_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id')->unique();
            $table->string('name');
            $table->string('normalized_name')->index();
            $table->string('position')->nullable();
            $table->string('nationality')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->unsignedBigInteger('team_provider_id')->nullable();
            $table->string('team_name')->nullable();
            $table->text('image_url')->nullable();
            $table->text('profile_url')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('football_players');
    }
};
