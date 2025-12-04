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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            
            // Participants
            $table->foreignId('user1_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            $table->foreignId('user2_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Related Booking (optional)
            $table->foreignId('booking_id')
                  ->nullable()
                  ->constrained('bookings')
                  ->onDelete('set null');
            
            // Last Message Info
            $table->text('last_message')->nullable();
            $table->foreignId('last_message_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('last_message_at')->nullable();
            
            // Status
            $table->boolean('is_blocked')->default(false);
            $table->foreignId('blocked_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user1_id');
            $table->index('user2_id');
            $table->index('booking_id');
            $table->index(['user1_id', 'user2_id']);
            
            // Ensure unique conversation between two users
            $table->unique(['user1_id', 'user2_id', 'booking_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};