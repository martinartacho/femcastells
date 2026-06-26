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
            $table->string('name', 100)->nullable()->change();
            $table->string('last_name', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('castellers', function (Blueprint $table) {
            $table->string('name', 100)->nullable(false)->change();
            $table->string('last_name', 100)->nullable(false)->change();
        });
    }
};
