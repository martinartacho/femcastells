<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colles', function (Blueprint $table): void {
            $table->string('rondes_display')->default('cards')->after('projected_snapshot_id');
        });
    }

    public function down(): void
    {
        Schema::table('colles', function (Blueprint $table): void {
            $table->dropColumn('rondes_display');
        });
    }
};
