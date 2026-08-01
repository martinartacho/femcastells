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
            $table->string('stripe_account_id')->nullable()->after('display_token');
            $table->timestamp('stripe_onboarded_at')->nullable()->after('stripe_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('colles', function (Blueprint $table) {
            $table->dropColumn(['stripe_account_id', 'stripe_onboarded_at']);
        });
    }
};
