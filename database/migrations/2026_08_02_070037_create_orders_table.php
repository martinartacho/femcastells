<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colla_id')->constrained('colles')->cascadeOnDelete();
            $table->foreignId('casteller_id')->nullable()->constrained('castellers')->nullOnDelete();
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable();
            $table->string('status')->default('pending');
            $table->decimal('subtotal', 8, 2)->default(0);
            $table->decimal('tax_total', 8, 2)->default(0);
            $table->decimal('total', 8, 2)->default(0);
            $table->string('stripe_checkout_session_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
