<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('league_effective_selections', function (Blueprint $table): void {
            $table->dropUnique(['league_id', 'user_id', 'slot_key']);
            $table->dropUnique(['league_id', 'player_id']);
            $table->unique(['league_id', 'season_id', 'user_id', 'slot_key'], 'les_league_season_user_slot_unique');
            $table->unique(['league_id', 'season_id', 'player_id'], 'les_league_season_player_unique');
        });
    }

    public function down(): void
    {
        Schema::table('league_effective_selections', function (Blueprint $table): void {
            $table->dropUnique('les_league_season_user_slot_unique');
            $table->dropUnique('les_league_season_player_unique');
            $table->unique(['league_id', 'user_id', 'slot_key']);
            $table->unique(['league_id', 'player_id']);
        });
    }
};
