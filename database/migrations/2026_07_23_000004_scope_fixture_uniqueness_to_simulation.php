<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('league_matches', function (Blueprint $table): void {
            $table->dropUnique('league_matches_league_id_fixture_id_unique');
            $table->unique(['simulation_id', 'fixture_id']);
        });
    }

    public function down(): void
    {
        Schema::table('league_matches', function (Blueprint $table): void {
            $table->dropUnique('league_matches_simulation_id_fixture_id_unique');
            $table->unique(['league_id', 'fixture_id']);
        });
    }
};
