<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('league_user', function (Blueprint $table): void {
            $table->string('team_name', 80)->nullable()->after('user_id');
            $table->string('team_logo_path')->nullable()->after('team_name');
        });
    }

    public function down(): void
    {
        Schema::table('league_user', function (Blueprint $table): void {
            $table->dropColumn(['team_name', 'team_logo_path']);
        });
    }
};
