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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('listing_id')->constrained('service_listings')->onDelete('cascade');
            
            // Booking details
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('hours', 5, 2);
            $table->decimal('hourly_rate', 8, 2);
            $table->decimal('total_amount', 10, 2);
            $table->text('service_location');
            $table->text('special_requirements')->nullable();
            
            // Status tracking
            $table->enum('status', [
                'pending',
                'accepted',
                'rejected',
                'in_progress',
                'completed',
                'cancelled'
            ])->default('pending');
            
            // Cancellation details
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            
            // Acceptance/Rejection
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            
            // Completion
            $table->timestamp('completed_at')->nullable();
            
            // Payment (for future integration)
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'failed'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('client_id');
            $table->index('provider_id');
            $table->index('listing_id');
            $table->index('booking_date');
            $table->index('status');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};