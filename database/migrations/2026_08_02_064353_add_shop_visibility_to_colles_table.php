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
            $table->string('shop_visibility')->default('disabled')->after('stripe_onboarded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('colles', function (Blueprint $table) {
            $table->dropColumn('shop_visibility');
        });
    }
};
