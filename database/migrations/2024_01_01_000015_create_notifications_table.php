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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Notification Type
            $table->enum('type', [
                'booking_created',
                'booking_accepted',
                'booking_rejected',
                'booking_cancelled',
                'booking_completed',
                'payment_received',
                'payment_failed',
                'payment_refunded',
                'payout_processed',
                'message_received',
                'review_received',
                'review_response',
                'document_approved',
                'document_rejected',
                'listing_approved',
                'listing_rejected',
                'system_announcement',
                'promotional',
            ]);
            
            // Notification Content
            $table->string('title');
            $table->text('message');
            
            // Related Entity (polymorphic)
            $table->string('related_type')->nullable(); // e.g., 'App\Models\Booking'
            $table->unsignedBigInteger('related_id')->nullable();
            
            // Action URL
            $table->string('action_url')->nullable();
            
            // Notification Data (JSON)
            $table->json('data')->nullable();
            
            // Status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            // Delivery Channels
            $table->boolean('sent_in_app')->default(true);
            $table->boolean('sent_email')->default(false);
            $table->boolean('sent_push')->default(false);
            $table->boolean('sent_sms')->default(false);
            
            // Delivery Status
            $table->timestamp('email_sent_at')->nullable();
            $table->timestamp('push_sent_at')->nullable();
            $table->timestamp('sms_sent_at')->nullable();
            
            // Priority
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('type');
            $table->index('is_read');
            $table->index(['related_type', 'related_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};