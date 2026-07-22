<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('football_players', function (Blueprint $table) {
            $table->string('known_name')->nullable()->after('name');
            $table->string('first_name')->nullable()->after('known_name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->unsignedSmallInteger('height_cm')->nullable()->after('age');
        });
    }

    public function down(): void
    {
        Schema::table('football_players', function (Blueprint $table) {
            $table->dropColumn(['known_name', 'first_name', 'last_name', 'height_cm']);
        });
    }
};
