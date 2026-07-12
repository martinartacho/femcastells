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
        Schema::table('board_snapshots', function (Blueprint $table) {
            $table->uuid('public_token')->nullable()->unique()->after('is_primary');
        });
    }

    public function down(): void
    {
        Schema::table('board_snapshots', function (Blueprint $table) {
            $table->dropColumn('public_token');
        });
    }
};
