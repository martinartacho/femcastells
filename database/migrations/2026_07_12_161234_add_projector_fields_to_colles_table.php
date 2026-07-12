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
            $table->uuid('display_token')->nullable()->unique()->after('gdpr_email');
            $table->foreignId('projected_snapshot_id')->nullable()->constrained('board_snapshots')->nullOnDelete()->after('display_token');
        });
    }

    public function down(): void
    {
        Schema::table('colles', function (Blueprint $table) {
            $table->dropForeign(['projected_snapshot_id']);
            $table->dropColumn(['display_token', 'projected_snapshot_id']);
        });
    }
};
