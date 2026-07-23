<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('leagues', 'owner_id')) {
            Schema::table('leagues', function (Blueprint $table) {
                $table->unsignedBigInteger('owner_id')->nullable()->after('icon');
            });

            foreach (DB::table('leagues')->get() as $league) {
                $owner = DB::table('league_user')->where('league_id', $league->id)->orderBy('id')->value('user_id');
                if ($owner) DB::table('leagues')->where('id', $league->id)->update(['owner_id' => $owner]);
            }
        }

        if (! Schema::hasColumn('league_user', 'ready_at')) {
            Schema::table('league_user', function (Blueprint $table) {
                $table->timestamp('ready_at')->nullable()->after('user_id');
            });
        }

        if (Schema::hasColumn('leagues', 'start_at')) {
            Schema::table('leagues', function (Blueprint $table) {
                $table->dropColumn(['start_at', 'end_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
        });
        Schema::table('league_user', function (Blueprint $table) {
            $table->dropColumn('ready_at');
        });
    }
};
