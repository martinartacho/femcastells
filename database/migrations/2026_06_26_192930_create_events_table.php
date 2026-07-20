<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colla_id')->constrained('colles')->cascadeOnDelete();
            $table->string('name');
            $table->tinyInteger('type')->default(1)->comment('1=assaig,2=actuació,3=activitat');
            $table->dateTime('start_date');
            $table->dateTime('open_date')->nullable()->comment('obertura assistència');
            $table->dateTime('close_date')->nullable()->comment('tancament assistència');
            $table->unsignedSmallInteger('duration')->nullable()->comment('minuts');
            $table->string('address')->nullable();
            $table->boolean('visibility')->default(true);
            $table->boolean('companions')->default(false);
            $table->uuid('recurring_group_id')->nullable()->index();
            $table->string('public_token')->nullable()->unique();
            $table->string('photo')->nullable();
            $table->text('comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
