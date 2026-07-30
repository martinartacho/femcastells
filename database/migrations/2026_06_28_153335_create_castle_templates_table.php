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
        Schema::create('castle_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colla_id')->constrained('colles')->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('key', 100);
            $table->string('svg_path')->nullable();
            $table->json('positions')->nullable();
            $table->boolean('ready')->default(false);
            $table->timestamps();

            $table->unique(['colla_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('castle_templates');
    }
};
