<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->integer('player_one_score')->default(0);
            $table->integer('player_two_score')->default(0);
            $table->integer('rematch_count')->default(0); // jumlah rematch antar kedua pemain
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['player_one_score', 'player_two_score', 'rematch_count']);
        });
    }
};
