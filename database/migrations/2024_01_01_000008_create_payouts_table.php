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
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('provider_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            $table->foreignId('payment_id')
                  ->nullable()
                  ->constrained('payments')
                  ->onDelete('set null');
            
            // Payout Details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            
            // Stripe Details
            $table->string('stripe_payout_id')->nullable()->unique();
            $table->string('stripe_account_id')->nullable(); // Provider's Stripe Connect account
            
            // Payout Status
            $table->enum('status', [
                'pending',
                'processing',
                'paid',
                'failed',
                'canceled'
            ])->default('pending');
            
            // Bank Details (if manual payout)
            $table->string('bank_name')->nullable();
            $table->string('account_number_last4')->nullable();
            
            // Timestamps
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            
            // Metadata
            $table->text('failure_reason')->nullable();
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('provider_id');
            $table->index('payment_id');
            $table->index('status');
            $table->index('stripe_payout_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};