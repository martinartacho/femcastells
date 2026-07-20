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
            $table->unsignedInteger('num_soci')->nullable();
            $table->string('alias');
            $table->string('name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->char('gender', 1)->nullable();
            $table->string('email')->nullable();
            $table->string('email2')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->string('phone')->nullable();
            $table->string('mobile_phone', 20)->nullable();
            $table->string('emergency_phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('nationality', 50)->nullable();
            $table->string('national_id', 50)->nullable();
            $table->string('national_id_type', 50)->nullable();
            $table->date('birthdate')->nullable();
            $table->date('subscription_date')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=actiu,2=inactiu,3=baixa,4=candidat');
            $table->unsignedSmallInteger('height')->nullable()->comment('cm');
            $table->unsignedSmallInteger('weight')->nullable()->comment('kg');
            $table->unsignedSmallInteger('shoulder_height')->nullable()->comment('cm');
            $table->string('photo')->nullable();
            $table->text('comments')->nullable();
            $table->string('family', 150)->nullable();
            $table->boolean('family_head')->nullable();
            $table->timestamp('noticies_last_seen_at')->nullable();
            $table->timestamp('gdpr_accepted_at')->nullable();
            $table->string('gdpr_version', 20)->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->unsignedInteger('login_count')->default(0);
            $table->timestamps();

            $table->unique(['colla_id', 'alias']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('castellers');
    }
};
