<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colles', function (Blueprint $table) {
            $table->unsignedTinyInteger('assaigs_weeks_visible')->default(2)->after('max_members');
        });
    }

    public function down(): void
    {
        Schema::table('colles', function (Blueprint $table) {
            $table->dropColumn('assaigs_weeks_visible');
        });
    }
};
