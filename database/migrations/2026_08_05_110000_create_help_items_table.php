<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_items', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('visible')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_items');
    }
};
