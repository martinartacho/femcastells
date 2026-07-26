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
        Schema::table('colles', function (Blueprint $table) {
            $table->json('board_settings')->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('colles', function (Blueprint $table) {
            $table->dropColumn('board_settings');
        });
    }
};
