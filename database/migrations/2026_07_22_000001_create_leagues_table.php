<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leagues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 5)->unique();
            $table->unsignedInteger('max_users');
            $table->string('icon')->default('bx bx-football');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('yet_to_start');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leagues');
    }
};
