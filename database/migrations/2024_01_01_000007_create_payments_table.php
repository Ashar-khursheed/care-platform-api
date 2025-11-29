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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('booking_id')
                  ->constrained('bookings')
                  ->onDelete('cascade');
            
            $table->foreignId('client_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            $table->foreignId('provider_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Payment Details
            $table->decimal('amount', 10, 2); // Total amount
            $table->decimal('platform_fee', 10, 2)->default(0); // Commission
            $table->decimal('provider_amount', 10, 2); // Amount to provider
            $table->string('currency', 3)->default('USD');
            
            // Stripe Details
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->string('payment_method_id')->nullable();
            
            // Payment Method
            $table->enum('payment_method_type', ['card', 'wallet', 'bank_transfer'])->default('card');
            $table->string('card_brand')->nullable(); // visa, mastercard, etc
            $table->string('card_last4')->nullable();
            
            // Payment Status
            $table->enum('status', [
                'pending',
                'requires_payment_method',
                'requires_confirmation',
                'requires_action',
                'processing',
                'succeeded',
                'failed',
                'canceled',
                'refunded',
                'partially_refunded'
            ])->default('pending');
            
            // Timestamps
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            
            // Refund Details
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('refund_reason')->nullable();
            
            // Metadata
            $table->text('stripe_error')->nullable();
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('booking_id');
            $table->index('client_id');
            $table->index('provider_id');
            $table->index('status');
            $table->index('stripe_payment_intent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};