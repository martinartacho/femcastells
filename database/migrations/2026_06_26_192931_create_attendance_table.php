<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('casteller_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['ok', 'nok', 'unknown'])->default('unknown');
            $table->unsignedTinyInteger('companions')->default(0);
            $table->boolean('checked_in')->nullable()->default(null);
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'casteller_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
