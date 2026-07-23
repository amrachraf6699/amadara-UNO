<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('squad_selections', 'football_player_id')) {
            Schema::dropIfExists('football_players');
            return;
        }

        Schema::table('squad_selections', function (Blueprint $table) {
            $table->unsignedBigInteger('player_id')->nullable()->after('league_id');
            $table->json('player_data')->nullable()->after('player_id');
        });

        foreach (DB::table('squad_selections')->get() as $selection) {
            $player = DB::table('football_players')->where('id', $selection->football_player_id)->first();
            if ($player) {
                DB::table('squad_selections')->where('id', $selection->id)->update([
                    'player_id' => $player->provider_id,
                    'player_data' => json_encode([
                        'id' => $player->provider_id,
                        'name' => $player->name,
                        'known_name' => $player->known_name,
                        'first_name' => $player->first_name,
                        'last_name' => $player->last_name,
                        'nationality' => $player->nationality,
                        'age' => $player->age,
                        'height_cm' => $player->height_cm,
                        'position' => $player->position,
                        'team_name' => $player->team_name,
                        'image_url' => $player->image_url,
                    ]),
                ]);
            }
        }

        if (DB::connection()->getDriverName() !== 'sqlite') {
            Schema::table('squad_selections', function (Blueprint $table) {
                $table->dropForeign(['football_player_id']);
            });
        }

        Schema::table('squad_selections', function (Blueprint $table) {
            $table->dropUnique('squad_selections_league_id_football_player_id_unique');
            $table->dropColumn('football_player_id');
            $table->unique(['league_id', 'player_id']);
        });
        Schema::dropIfExists('football_players');
    }

    public function down(): void
    {
        // The local JSON catalogue is the source of truth; the removed cache table is not restored.
    }
};
