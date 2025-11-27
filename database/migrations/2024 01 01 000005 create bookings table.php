<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_number')->unique();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('listing_id')->constrained('service_listings')->onDelete('cascade');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('hours')->default(1);
            $table->decimal('hourly_rate', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('provider_earnings', 10, 2);
            $table->text('special_instructions')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', [
                'pending',
                'confirmed',
                'in_progress',
                'completed',
                'cancelled',
                'disputed'
            ])->default('pending');
            $table->enum('payment_status', [
                'pending',
                'paid',
                'refunded',
                'failed'
            ])->default('pending');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('booking_number');
            $table->index('client_id');
            $table->index('provider_id');
            $table->index('listing_id');
            $table->index('status');
            $table->index('payment_status');
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};