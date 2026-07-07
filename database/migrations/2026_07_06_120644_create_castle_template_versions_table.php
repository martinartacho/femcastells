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
        Schema::create('castle_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('castle_templates')->cascadeOnDelete();
            $table->json('positions');
            $table->string('label', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('castle_template_versions');
    }
};
