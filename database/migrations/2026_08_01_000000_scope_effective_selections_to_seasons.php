<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureLeaguePlayerLookupIndex();
        $this->dropLegacyUniqueIndexes();

        Schema::table('league_effective_selections', function (Blueprint $table): void {
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

    private function dropLegacyUniqueIndexes(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            Schema::table('league_effective_selections', function (Blueprint $table): void {
                $table->dropUnique(['league_id', 'user_id', 'slot_key']);
                $table->dropUnique(['league_id', 'player_id']);
            });

            return;
        }

        $legacyColumns = ['league_id,user_id,slot_key', 'league_id,player_id'];
        $indexNames = collect(DB::select(<<<'SQL'
            SELECT index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS indexed_columns
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name = 'league_effective_selections'
              AND non_unique = 0
            GROUP BY index_name
        SQL))->filter(fn (object $index): bool => in_array($index->indexed_columns, $legacyColumns, true))
            ->pluck('index_name');

        if ($indexNames->isEmpty()) return;

        Schema::table('league_effective_selections', function (Blueprint $table) use ($indexNames): void {
            foreach ($indexNames as $indexName) $table->dropUnique($indexName);
        });
    }

    private function ensureLeaguePlayerLookupIndex(): void
    {
        $indexName = 'les_league_player_lookup_index';

        if (DB::getDriverName() === 'mysql') {
            $exists = DB::table('information_schema.statistics')
                ->where('table_schema', DB::raw('DATABASE()'))
                ->where('table_name', 'league_effective_selections')
                ->where('index_name', $indexName)
                ->exists();

            if ($exists) return;
        }

        Schema::table('league_effective_selections', function (Blueprint $table) use ($indexName): void {
            $table->index(['league_id', 'player_id'], $indexName);
        });
    }
};
