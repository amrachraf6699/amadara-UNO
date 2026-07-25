<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('squad_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('formation', 10);
            $table->timestamps();
            $table->unique(['league_id', 'user_id']);
        });

        Schema::create('league_player_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('player_id');
            $table->json('player_data');
            $table->string('slot_key', 30);
            $table->string('role', 10);
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->unique(['league_id', 'player_id']);
            $table->unique(['league_id', 'user_id', 'slot_key']);
        });

        DB::table('squad_selections')->orderBy('id')->each(function (object $selection): void {
            $lockedAt = DB::table('squads')->where('id', $selection->squad_id)->value('locked_at');
            DB::table('league_player_reservations')->insert([
                'league_id' => $selection->league_id, 'user_id' => DB::table('squads')->where('id', $selection->squad_id)->value('user_id'),
                'player_id' => $selection->player_id, 'player_data' => $selection->player_data, 'slot_key' => $selection->slot_key,
                'role' => $selection->role, 'locked_at' => $lockedAt, 'created_at' => $selection->created_at, 'updated_at' => $selection->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('league_player_reservations');
        Schema::dropIfExists('squad_drafts');
    }
};
