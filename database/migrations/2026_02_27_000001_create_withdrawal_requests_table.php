<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Creates the withdrawal_requests table for the Upwork-style escrow + payout flow.
     *
     * Commission Model (Option A - 10% from both sides):
     *   - Client pays: booking_total + 10% (client_fee)
     *   - Provider earns: booking_total - 10% (provider_fee)
     *   - Platform collects: client_fee + provider_fee (~20% of booking total)
     *
     * Uses string() instead of enum() for cross-database (SQLite) compatibility.
     */
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();

            $table->foreignId('provider_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->foreignId('booking_id')
                  ->nullable()
                  ->constrained('bookings')
                  ->onDelete('set null');

            // Amount Breakdown (10% from both sides)
            $table->decimal('gross_amount', 10, 2);
            $table->decimal('client_fee', 10, 2)->default(0);
            $table->decimal('provider_fee', 10, 2)->default(0);
            $table->decimal('platform_fee_total', 10, 2)->default(0);
            $table->decimal('net_provider_amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            // Escrow Status: holding | released | disputed
            $table->string('escrow_status', 20)->default('holding');

            // Withdrawal Status: none | requested | approved | rejected | paid | cancelled
            $table->string('withdrawal_status', 20)->default('none');

            // Escrow Timestamps
            $table->timestamp('escrow_held_at')->nullable();
            $table->timestamp('auto_release_at')->nullable();
            $table->timestamp('escrow_released_at')->nullable();

            // Withdrawal Timestamps
            $table->timestamp('withdrawal_requested_at')->nullable();
            $table->timestamp('withdrawal_processed_at')->nullable();

            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('account_number_last4')->nullable();

            // Notes
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            // Tracking
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('transaction_reference')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('provider_id');
            $table->index('booking_id');
            $table->index('escrow_status');
            $table->index('withdrawal_status');
            $table->index('auto_release_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
