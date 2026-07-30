<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colla_id')->constrained('colles')->cascadeOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->string('castle_type', 60)->default('3d6');
            $table->boolean('is_primary')->default(false);
            $table->uuid('public_token')->nullable()->unique();
            $table->unsignedTinyInteger('ronda_number')->nullable();
            $table->string('name', 100);
            $table->json('assignments');
            $table->timestamps();

            $table->index(['colla_id', 'event_id']);
            $table->index(['event_id', 'ronda_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_snapshots');
    }
};
