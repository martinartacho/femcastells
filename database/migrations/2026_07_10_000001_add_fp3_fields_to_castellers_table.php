<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('castellers', function (Blueprint $table) {
            $table->unsignedInteger('num_soci')->nullable()->after('colla_id');
            $table->tinyInteger('gender')->nullable()->after('last_name')->comment('1=home,2=dona,3=no binari');
            $table->string('email2')->nullable()->after('email');
            $table->string('mobile_phone', 20)->nullable()->after('phone');
            $table->string('emergency_phone', 20)->nullable()->after('mobile_phone');
            $table->string('address')->nullable()->after('emergency_phone');
            $table->string('postal_code', 10)->nullable()->after('address');
            $table->string('city', 100)->nullable()->after('postal_code');
            $table->string('province', 100)->nullable()->after('city');
            $table->string('country', 100)->nullable()->after('province');
            $table->string('nationality', 50)->nullable()->after('country');
            $table->string('national_id', 50)->nullable()->after('nationality');
            $table->string('national_id_type', 50)->nullable()->after('national_id');
            $table->date('subscription_date')->nullable()->after('birthdate');
            $table->string('family', 150)->nullable()->after('comments');
            $table->boolean('family_head')->nullable()->after('family');
        });
    }

    public function down(): void
    {
        Schema::table('castellers', function (Blueprint $table) {
            $table->dropColumn([
                'num_soci', 'gender', 'email2', 'mobile_phone', 'emergency_phone',
                'address', 'postal_code', 'city', 'province', 'country', 'nationality',
                'national_id', 'national_id_type', 'subscription_date', 'family', 'family_head',
            ]);
        });
    }
};
