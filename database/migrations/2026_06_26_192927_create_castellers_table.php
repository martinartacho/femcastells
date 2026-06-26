<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('castellers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colla_id')->constrained('colles')->cascadeOnDelete();
            $table->string('alias');
            $table->string('name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('birthdate')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=actiu,2=inactiu,3=baixa,4=candidat');
            $table->unsignedSmallInteger('height')->nullable()->comment('cm');
            $table->unsignedSmallInteger('weight')->nullable()->comment('kg');
            $table->unsignedSmallInteger('shoulder_height')->nullable()->comment('cm');
            $table->string('photo')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['colla_id', 'alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('castellers');
    }
};
