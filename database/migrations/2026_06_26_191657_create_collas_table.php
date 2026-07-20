<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
            $table->unsignedTinyInteger('assaigs_weeks_visible')->default(2);
            $table->text('gdpr_text')->nullable();
            $table->string('gdpr_version', 20)->default('1.0');
            $table->string('gdpr_email', 255)->nullable();
            $table->uuid('display_token')->nullable()->unique();
            // FK sense constraint: board_snapshots es crea en una migració posterior
            $table->unsignedBigInteger('projected_snapshot_id')->nullable()->index();
            $table->string('rondes_display')->default('cards');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colles');
    }
};
