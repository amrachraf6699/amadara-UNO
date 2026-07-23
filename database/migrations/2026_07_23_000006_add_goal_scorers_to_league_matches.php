<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('league_matches', function (Blueprint $table): void {
            $table->json('goal_scorers')->nullable()->after('away_score');
        });
    }

    public function down(): void
    {
        Schema::table('league_matches', function (Blueprint $table): void {
            $table->dropColumn('goal_scorers');
        });
    }
};
