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
        Schema::create('reviews', function (Blueprint $table) {
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
            
            $table->foreignId('listing_id')
                  ->constrained('service_listings')
                  ->onDelete('cascade');
            
            // Review Content
            $table->tinyInteger('rating')->unsigned(); // 1-5
            $table->text('comment')->nullable();
            
            // Provider Response
            $table->text('provider_response')->nullable();
            $table->timestamp('response_date')->nullable();
            
            // Moderation
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('approved');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users');
            $table->timestamp('moderated_at')->nullable();
            
            // Engagement
            $table->boolean('is_flagged')->default(false);
            $table->text('flag_reason')->nullable();
            $table->integer('helpful_count')->default(0);
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('booking_id');
            $table->index('client_id');
            $table->index('provider_id');
            $table->index('listing_id');
            $table->index('rating');
            $table->index('status');
            $table->index('is_flagged');
            
            // Unique constraint: One review per booking
            $table->unique('booking_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};