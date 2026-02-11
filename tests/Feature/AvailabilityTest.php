<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ServiceCategory;
use App\Models\ServiceListing;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepted_jobs_do_not_appear_in_job_listings()
    {
        $client = User::factory()->create(['user_type' => 'client']);
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
        
        // Active Job
        ServiceListing::create([
            'provider_id' => $client->id,
            'category_id' => $category->id,
            'title' => 'Available Job',
            'description' => 'This job should be visible in the list.',
            'hourly_rate' => 50,
            'service_location' => 'New York',
            'is_available' => true,
            'status' => 'active',
        ]);

        // Job that was accepted (marked as unavailable)
        ServiceListing::create([
            'provider_id' => $client->id,
            'category_id' => $category->id,
            'title' => 'Accepted Job',
            'description' => 'This job should NOT be visible in the list.',
            'hourly_rate' => 50,
            'service_location' => 'New York',
            'is_available' => false,
            'status' => 'active',
        ]);

        $response = $this->getJson('/api/v1/jobs');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.jobs')
            ->assertJsonPath('data.jobs.0.title', 'Available Job');
    }

    public function test_providers_with_overlapping_bookings_are_filtered_out()
    {
        $client = User::factory()->create(['user_type' => 'client']);
        $provider = User::factory()->create(['user_type' => 'provider']);
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);

        ServiceListing::create([
            'provider_id' => $provider->id,
            'category_id' => $category->id,
            'title' => 'Expert Plumber',
            'description' => 'I can fix anything related to plumbing in your house.',
            'hourly_rate' => 60,
            'service_location' => 'New York',
            'is_available' => true,
            'status' => 'active',
        ]);

        // Create a booking for this provider
        Booking::create([
            'client_id' => $client->id,
            'provider_id' => $provider->id,
            'listing_id' => 1, // Mock ID
            'booking_date' => '2026-03-01',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'hours' => 2,
            'hourly_rate' => 60,
            'total_amount' => 120,
            'service_location' => 'New York',
            'status' => 'accepted',
        ]);

        // Scenario 1: Search for overlapping time
        $response = $this->getJson('/api/v1/listings?booking_date=2026-03-01&start_time=11:00:00&end_time=13:00:00');
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.listings');

        // Scenario 2: Search for non-overlapping time on same day
        $response = $this->getJson('/api/v1/listings?booking_date=2026-03-01&start_time=14:00:00&end_time=16:00:00');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.listings');

        // Scenario 3: Search for different day
        $response = $this->getJson('/api/v1/listings?booking_date=2026-03-02&start_time=10:00:00&end_time=12:00:00');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.listings');
    }
}
