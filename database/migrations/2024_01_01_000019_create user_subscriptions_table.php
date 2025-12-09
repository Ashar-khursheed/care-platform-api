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
        Schema::create('user_subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Foreign Keys
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            
            $table->foreignId('subscription_plan_id')
                  ->constrained('subscription_plans')
                  ->onDelete('cascade');
            
            // Stripe Integration
            $table->string('stripe_subscription_id')->unique()->nullable();
            $table->string('stripe_customer_id')->nullable();
            
            // Billing
            $table->enum('billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            
            // Subscription Status
            $table->enum('status', [
                'trial',
                'active',
                'past_due',
                'canceled',
                'expired',
                'paused'
            ])->default('trial');
            
            // Dates
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('paused_at')->nullable();
            
            // Usage Tracking
            $table->integer('listings_used')->default(0);
            $table->integer('bookings_used')->default(0);
            $table->timestamp('usage_reset_at')->nullable(); // Reset monthly
            
            // Auto-renewal
            $table->boolean('auto_renew')->default(true);
            
            // Metadata
            $table->text('cancellation_reason')->nullable();
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('user_id');
            $table->index('subscription_plan_id');
            $table->index('status');
            $table->index('stripe_subscription_id');
            $table->index('ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_subscriptions');
    }
};