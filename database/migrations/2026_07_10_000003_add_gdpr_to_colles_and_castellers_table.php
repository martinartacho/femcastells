<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colles', function (Blueprint $table) {
            $table->text('gdpr_text')->nullable()->after('max_members');
            $table->string('gdpr_version', 20)->default('1.0')->after('gdpr_text');
            $table->string('gdpr_email', 255)->nullable()->after('gdpr_version');
        });

        Schema::table('castellers', function (Blueprint $table) {
            $table->timestamp('gdpr_accepted_at')->nullable()->after('password');
            $table->string('gdpr_version', 20)->nullable()->after('gdpr_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('colles', function (Blueprint $table) {
            $table->dropColumn(['gdpr_text', 'gdpr_version', 'gdpr_email']);
        });

        Schema::table('castellers', function (Blueprint $table) {
            $table->dropColumn(['gdpr_accepted_at', 'gdpr_version']);
        });
    }
};
