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
            $table->boolean('bizum_enabled')->default(false)->after('shop_visibility');
            $table->string('bizum_phone', 20)->nullable()->after('bizum_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('colles', function (Blueprint $table) {
            $table->dropColumn(['bizum_enabled', 'bizum_phone']);
        });
    }
};
