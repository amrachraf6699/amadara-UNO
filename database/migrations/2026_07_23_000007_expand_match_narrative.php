<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('league_matches', function (Blueprint $table): void {
            $table->text('narrative')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('league_matches', function (Blueprint $table): void {
            $table->string('narrative', 280)->nullable()->change();
        });
    }
};
