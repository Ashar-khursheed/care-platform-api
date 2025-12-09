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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            
            // Plan Details
            $table->string('name'); // Free, Basic, Premium, Enterprise
            $table->string('slug')->unique(); // free, basic, premium, enterprise
            $table->text('description')->nullable();
            
            // Pricing
            $table->decimal('price', 10, 2)->default(0); // Monthly price
            $table->decimal('yearly_price', 10, 2)->nullable(); // Yearly price (discounted)
            $table->string('currency', 3)->default('USD');
            
            // Stripe Integration
            $table->string('stripe_plan_id')->nullable(); // Stripe price ID for monthly
            $table->string('stripe_yearly_plan_id')->nullable(); // Stripe price ID for yearly
            
            // Plan Limits
            $table->integer('max_listings')->default(0); // 0 = unlimited
            $table->integer('max_bookings_per_month')->default(0); // 0 = unlimited
            $table->boolean('featured_listings_allowed')->default(false);
            $table->integer('max_featured_listings')->default(0);
            $table->boolean('priority_support')->default(false);
            $table->boolean('analytics_access')->default(false);
            $table->boolean('api_access')->default(false);
            
            // Trial
            $table->integer('trial_days')->default(0); // 0 = no trial
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false); // Featured as "Most Popular"
            $table->integer('sort_order')->default(0);
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('slug');
            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};