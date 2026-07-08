<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('noticies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colla_id')->nullable()->constrained('colles')->nullOnDelete();
            $table->foreignId('tag_id')->nullable()->constrained('tags')->nullOnDelete();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('body');
            $table->json('labels')->nullable();
            $table->string('version', 20)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('noticies');
    }
};
