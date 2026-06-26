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
        Schema::create('colles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shortname')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('city')->nullable();
            $table->string('country', 2)->default('ES');
            $table->string('logo')->nullable();
            $table->string('color', 7)->nullable();
            $table->unsignedInteger('max_members')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colles');
    }
};
