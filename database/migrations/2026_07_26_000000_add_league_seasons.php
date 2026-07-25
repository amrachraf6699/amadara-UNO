<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('league_seasons', function (Blueprint $table) {
            $table->id(); $table->foreignId('league_id')->constrained()->cascadeOnDelete(); $table->unsignedInteger('number');
            $table->string('status', 20); $table->timestamp('started_at')->nullable(); $table->timestamp('completed_at')->nullable(); $table->timestamps();
            $table->unique(['league_id', 'number']);
        });
        Schema::create('league_season_selections', function (Blueprint $table) {
            $table->id(); $table->foreignId('season_id')->constrained('league_seasons')->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('player_id'); $table->json('player_data'); $table->string('slot_key', 30); $table->string('role', 10); $table->timestamps();
            $table->unique(['season_id', 'player_id']); $table->unique(['season_id', 'user_id', 'slot_key']);
        });
        Schema::create('league_season_readies', function (Blueprint $table) {
            $table->id(); $table->foreignId('season_id')->constrained('league_seasons')->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->timestamp('ready_at'); $table->timestamps(); $table->unique(['season_id', 'user_id']);
        });
        Schema::create('league_season_transfers', function (Blueprint $table) {
            $table->id(); $table->foreignId('season_id')->constrained('league_seasons')->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->unsignedBigInteger('outgoing_player_id'); $table->unsignedBigInteger('incoming_player_id'); $table->string('slot_key', 30); $table->timestamps(); $table->unique(['season_id', 'user_id', 'slot_key']);
        });
        Schema::table('leagues', fn (Blueprint $table) => $table->unsignedBigInteger('current_season_id')->nullable()->after('status'));
        foreach (['league_simulations', 'league_matches', 'league_standings', 'league_power_cards', 'league_effective_selections', 'league_player_reservations'] as $name) Schema::table($name, fn (Blueprint $table) => $table->unsignedBigInteger('season_id')->nullable()->after('league_id')->index());
        // Existing league-wide uniqueness is retained for SQLite compatibility.
        // Season fixtures are prefixed with their season ID, and active card/effective
        // records are reset when the next season opens.

        DB::table('leagues')->orderBy('id')->each(function (object $league): void {
            $seasonId = DB::table('league_seasons')->insertGetId(['league_id' => $league->id, 'number' => 1, 'status' => 'setup', 'created_at' => now(), 'updated_at' => now()]);
            DB::table('leagues')->where('id', $league->id)->update(['current_season_id' => $seasonId]);
            foreach (['league_simulations', 'league_matches', 'league_standings', 'league_power_cards', 'league_effective_selections', 'league_player_reservations'] as $table) DB::table($table)->where('league_id', $league->id)->update(['season_id' => $seasonId]);
            DB::table('squad_selections')->where('league_id', $league->id)->orderBy('id')->each(fn (object $row) => DB::table('league_season_selections')->insert(['season_id' => $seasonId, 'user_id' => DB::table('squads')->where('id', $row->squad_id)->value('user_id'), 'player_id' => $row->player_id, 'player_data' => $row->player_data, 'slot_key' => $row->slot_key, 'role' => $row->role, 'created_at' => $row->created_at, 'updated_at' => $row->updated_at]));
        });
    }
    public function down(): void { Schema::dropIfExists('league_season_transfers'); Schema::dropIfExists('league_season_readies'); Schema::dropIfExists('league_season_selections'); Schema::dropIfExists('league_seasons'); }
};
