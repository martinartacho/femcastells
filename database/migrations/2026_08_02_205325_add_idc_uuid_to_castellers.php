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
        Schema::table('castellers', function (Blueprint $table) {
            $table->string('idc_uuid')->nullable()->after('fcm_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('castellers', function (Blueprint $table) {
            $table->dropColumn('idc_uuid');
        });
    }
};
