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
            $table->boolean('show_logo_in_pinya')->default(false)->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('colles', function (Blueprint $table) {
            $table->dropColumn('show_logo_in_pinya');
        });
    }
};
