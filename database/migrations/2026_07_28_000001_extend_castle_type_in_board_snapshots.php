<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('board_snapshots', function (Blueprint $table) {
            $table->string('castle_type', 60)->default('3d6')->change();
        });
    }

    public function down(): void
    {
        Schema::table('board_snapshots', function (Blueprint $table) {
            $table->string('castle_type', 20)->default('3d6')->change();
        });
    }
};
