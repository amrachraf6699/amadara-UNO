<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_power_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('card_type', 20);
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('target_player_id')->nullable();
            $table->unsignedBigInteger('replacement_player_id')->nullable();
            $table->string('resolution_status', 20)->default('pending');
            $table->string('resolution_reason')->nullable();
            $table->json('resolution_data')->nullable();
            $table->timestamps();
            $table->unique(['league_id', 'user_id', 'card_type']);
            $table->index(['league_id', 'card_type']);
        });

        Schema::create('league_card_resolutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('power_card_id')->constrained('league_power_cards')->cascadeOnDelete();
            $table->string('card_type', 20);
            $table->boolean('applied')->default(false);
            $table->string('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique('power_card_id');
        });

        Schema::create('league_effective_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('player_id');
            $table->json('player_data');
            $table->string('slot_key', 30);
            $table->string('role', 10);
            $table->timestamps();
            $table->unique(['league_id', 'user_id', 'slot_key']);
            $table->unique(['league_id', 'player_id']);
        });

        Schema::create('league_simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('prompt_version', 40)->nullable();
            $table->string('prompt_hash', 64)->nullable();
            $table->string('model', 100)->nullable();
            $table->json('generation_options')->nullable();
            $table->string('request_payload_hash', 64)->nullable();
            $table->longText('raw_response')->nullable();
            $table->json('normalized_response')->nullable();
            $table->json('validation_errors')->nullable();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('league_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('simulation_id')->constrained('league_simulations')->cascadeOnDelete();
            $table->string('fixture_id', 100);
            $table->unsignedBigInteger('home_user_id');
            $table->unsignedBigInteger('away_user_id');
            $table->unsignedTinyInteger('leg');
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('home_score')->nullable();
            $table->unsignedSmallInteger('away_score')->nullable();
            $table->string('result', 20)->nullable();
            $table->smallInteger('home_points')->nullable();
            $table->smallInteger('away_points')->nullable();
            $table->unsignedBigInteger('boost_user_id')->nullable();
            $table->json('decisive_factors')->nullable();
            $table->json('player_impacts')->nullable();
            $table->string('narrative', 280)->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
            $table->unique(['league_id', 'fixture_id']);
            $table->index(['league_id', 'home_user_id', 'away_user_id']);
        });

        Schema::create('league_standings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('simulation_id')->constrained('league_simulations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('played')->default(0);
            $table->unsignedInteger('wins')->default(0);
            $table->unsignedInteger('draws')->default(0);
            $table->unsignedInteger('losses')->default(0);
            $table->unsignedInteger('goals_for')->default(0);
            $table->unsignedInteger('goals_against')->default(0);
            $table->integer('goal_difference')->default(0);
            $table->integer('points')->default(0);
            $table->timestamps();
            $table->unique(['simulation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_standings');
        Schema::dropIfExists('league_matches');
        Schema::dropIfExists('league_simulations');
        Schema::dropIfExists('league_effective_selections');
        Schema::dropIfExists('league_card_resolutions');
        Schema::dropIfExists('league_power_cards');
    }
};
