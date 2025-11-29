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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            $table->foreignId('payment_id')
                  ->nullable()
                  ->constrained('payments')
                  ->onDelete('set null');
            
            $table->foreignId('payout_id')
                  ->nullable()
                  ->constrained('payouts')
                  ->onDelete('set null');
            
            $table->foreignId('booking_id')
                  ->nullable()
                  ->constrained('bookings')
                  ->onDelete('set null');
            
            // Transaction Details
            $table->enum('type', [
                'payment',          // Client pays
                'refund',           // Client refund
                'payout',           // Provider receives
                'platform_fee',     // Platform commission
                'reversal'          // Chargeback/dispute
            ]);
            
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            
            // Balance Impact
            $table->enum('direction', ['credit', 'debit']); // Credit = money in, Debit = money out
            $table->decimal('balance_after', 10, 2)->nullable(); // Running balance
            
            // Transaction Status
            $table->enum('status', [
                'pending',
                'completed',
                'failed',
                'canceled'
            ])->default('completed');
            
            // Description
            $table->string('description');
            
            // Reference
            $table->string('reference_type')->nullable(); // e.g., 'booking', 'subscription'
            $table->unsignedBigInteger('reference_id')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('payment_id');
            $table->index('payout_id');
            $table->index('booking_id');
            $table->index('type');
            $table->index('status');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};