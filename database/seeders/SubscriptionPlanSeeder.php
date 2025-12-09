<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionFeature;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Free Plan
        $freePlan = SubscriptionPlan::create([
            'name' => 'Free',
            'slug' => 'free',
            'description' => 'Perfect for getting started',
            'price' => 0,
            'yearly_price' => 0,
            'currency' => 'USD',
            'max_listings' => 1,
            'max_bookings_per_month' => 5,
            'featured_listings_allowed' => false,
            'max_featured_listings' => 0,
            'priority_support' => false,
            'analytics_access' => false,
            'api_access' => false,
            'trial_days' => 0,
            'is_active' => true,
            'is_popular' => false,
            'sort_order' => 1,
        ]);

        SubscriptionFeature::insert([
            ['subscription_plan_id' => $freePlan->id, 'name' => '1 Service Listing', 'is_included' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $freePlan->id, 'name' => '5 Bookings per Month', 'is_included' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $freePlan->id, 'name' => 'Basic Support', 'is_included' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $freePlan->id, 'name' => 'Mobile App Access', 'is_included' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Basic Plan
        $basicPlan = SubscriptionPlan::create([
            'name' => 'Basic',
            'slug' => 'basic',
            'description' => 'Great for individuals and small providers',
            'price' => 29.99,
            'yearly_price' => 299.99, // Save ~17%
            'currency' => 'USD',
            'max_listings' => 5,
            'max_bookings_per_month' => 20,
            'featured_listings_allowed' => true,
            'max_featured_listings' => 1,
            'priority_support' => false,
            'analytics_access' => false,
            'api_access' => false,
            'trial_days' => 14,
            'is_active' => true,
            'is_popular' => false,
            'sort_order' => 2,
        ]);

        SubscriptionFeature::insert([
            ['subscription_plan_id' => $basicPlan->id, 'name' => '5 Service Listings', 'is_included' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $basicPlan->id, 'name' => '20 Bookings per Month', 'is_included' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $basicPlan->id, 'name' => '1 Featured Listing', 'is_included' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $basicPlan->id, 'name' => 'Email Support', 'is_included' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $basicPlan->id, 'name' => 'Basic Analytics', 'is_included' => true, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $basicPlan->id, 'name' => '14-Day Free Trial', 'is_included' => true, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Premium Plan (Most Popular)
        $premiumPlan = SubscriptionPlan::create([
            'name' => 'Premium',
            'slug' => 'premium',
            'description' => 'Perfect for growing businesses',
            'price' => 79.99,
            'yearly_price' => 799.99, // Save ~17%
            'currency' => 'USD',
            'max_listings' => 20,
            'max_bookings_per_month' => 100,
            'featured_listings_allowed' => true,
            'max_featured_listings' => 5,
            'priority_support' => true,
            'analytics_access' => true,
            'api_access' => false,
            'trial_days' => 30,
            'is_active' => true,
            'is_popular' => true,
            'sort_order' => 3,
        ]);

        SubscriptionFeature::insert([
            ['subscription_plan_id' => $premiumPlan->id, 'name' => '20 Service Listings', 'is_included' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $premiumPlan->id, 'name' => '100 Bookings per Month', 'is_included' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $premiumPlan->id, 'name' => '5 Featured Listings', 'is_included' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $premiumPlan->id, 'name' => 'Priority Support', 'is_included' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $premiumPlan->id, 'name' => 'Advanced Analytics', 'is_included' => true, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $premiumPlan->id, 'name' => 'Custom Branding', 'is_included' => true, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $premiumPlan->id, 'name' => '30-Day Free Trial', 'is_included' => true, 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Enterprise Plan
        $enterprisePlan = SubscriptionPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise',
            'description' => 'For large organizations with advanced needs',
            'price' => 199.99,
            'yearly_price' => 1999.99, // Save ~17%
            'currency' => 'USD',
            'max_listings' => 0, // Unlimited
            'max_bookings_per_month' => 0, // Unlimited
            'featured_listings_allowed' => true,
            'max_featured_listings' => 0, // Unlimited
            'priority_support' => true,
            'analytics_access' => true,
            'api_access' => true,
            'trial_days' => 30,
            'is_active' => true,
            'is_popular' => false,
            'sort_order' => 4,
        ]);

        SubscriptionFeature::insert([
            ['subscription_plan_id' => $enterprisePlan->id, 'name' => 'Unlimited Service Listings', 'is_included' => true, 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $enterprisePlan->id, 'name' => 'Unlimited Bookings', 'is_included' => true, 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $enterprisePlan->id, 'name' => 'Unlimited Featured Listings', 'is_included' => true, 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $enterprisePlan->id, 'name' => '24/7 Priority Support', 'is_included' => true, 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $enterprisePlan->id, 'name' => 'Enterprise Analytics', 'is_included' => true, 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $enterprisePlan->id, 'name' => 'API Access', 'is_included' => true, 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $enterprisePlan->id, 'name' => 'Dedicated Account Manager', 'is_included' => true, 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $enterprisePlan->id, 'name' => 'Custom Integrations', 'is_included' => true, 'sort_order' => 8, 'created_at' => now(), 'updated_at' => now()],
            ['subscription_plan_id' => $enterprisePlan->id, 'name' => 'White-label Solution', 'is_included' => true, 'sort_order' => 9, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->command->info('Subscription plans seeded successfully!');
    }
}