<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Convert any legacy integer values (1/2/3) to char (M/F/X)
        DB::statement("UPDATE castellers SET gender = CASE gender WHEN '1' THEN 'M' WHEN '2' THEN 'F' WHEN '3' THEN 'X' ELSE NULL END WHERE gender IS NOT NULL AND gender REGEXP '^[0-9]+$'");

        Schema::table('castellers', function (Blueprint $table) {
            $table->char('gender', 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('castellers', function (Blueprint $table) {
            $table->tinyInteger('gender')->nullable()->change();
        });
    }
};
