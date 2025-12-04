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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('conversation_id')
                  ->constrained('conversations')
                  ->onDelete('cascade');
            
            $table->foreignId('sender_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            $table->foreignId('receiver_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            // Message Content
            $table->text('message')->nullable();
            
            // Attachment
            $table->string('attachment_type')->nullable(); // image, document, audio, video
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->integer('attachment_size')->nullable(); // in KB
            
            // Message Status
            $table->enum('status', ['sent', 'delivered', 'read'])->default('sent');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            
            // Flags
            $table->boolean('is_edited')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->timestamp('deleted_at_by_sender')->nullable();
            $table->timestamp('deleted_at_by_receiver')->nullable();
            
            // Moderation
            $table->boolean('is_flagged')->default(false);
            $table->text('flag_reason')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('conversation_id');
            $table->index('sender_id');
            $table->index('receiver_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};