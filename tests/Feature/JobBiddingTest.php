<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\ServiceCategory;
use App\Models\ServiceListing;
use App\Models\Bid;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class JobBiddingTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_post_job()
    {
        $client = User::factory()->create(['user_type' => 'client']);
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);

        $response = $this->actingAs($client)->postJson('/api/v1/jobs', [
            'category_id' => $category->id,
            'title' => 'Fix my sink',
            'description' => 'Leaking sink needs urgent repair. Please bring your own tools and parts. This is a very serious issue.',
            'hourly_rate' => 50,
            'service_location' => 'New York',
            'availability' => ['monday' => '9-5'],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'Fix my sink');

        $this->assertDatabaseHas('service_listings', [
            'title' => 'Fix my sink',
            'provider_id' => $client->id,
        ]);
    }

    public function test_provider_can_bid_on_job()
    {
        $client = User::factory()->create(['user_type' => 'client']);
        $provider = User::factory()->create(['user_type' => 'provider']);
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
        
        $job = ServiceListing::create([
            'provider_id' => $client->id,
            'category_id' => $category->id,
            'title' => 'Fix my sink',
            'description' => 'Leaking sink',
            'hourly_rate' => 50,
            'service_location' => 'New York',
            'is_available' => true,
            'status' => 'active',
        ]);

        $response = $this->actingAs($provider)->postJson("/api/v1/jobs/{$job->id}/bids", [
            'amount' => 45,
            'message' => 'I can fix it',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('bids', [
            'listing_id' => $job->id,
            'provider_id' => $provider->id,
            'amount' => 45,
            'status' => 'pending',
        ]);
    }

    public function test_client_can_accept_bid()
    {
        $client = User::factory()->create(['user_type' => 'client']);
        $provider = User::factory()->create(['user_type' => 'provider']);
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
        
        $job = ServiceListing::create([
            'provider_id' => $client->id,
            'category_id' => $category->id,
            'title' => 'Fix my sink',
            'description' => 'Leaking sink',
            'hourly_rate' => 50,
            'service_location' => 'New York',
            'is_available' => true,
            'status' => 'active',
        ]);

        $bid = Bid::create([
            'listing_id' => $job->id,
            'provider_id' => $provider->id,
            'amount' => 45,
            'message' => 'I can fix it',
            'status' => 'pending',
        ]);

        $this->withoutExceptionHandling();
        $response = $this->actingAs($client)->postJson("/api/v1/bids/{$bid->id}/accept");

        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200);

        $this->assertDatabaseHas('bids', [
            'id' => $bid->id,
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('bookings', [
            'client_id' => $client->id,
            'provider_id' => $provider->id,
            'listing_id' => $job->id,
            'total_amount' => 45,
            'status' => 'accepted',
        ]);
    }

    public function test_provider_can_see_their_bids()
    {
        $client = User::factory()->create(['user_type' => 'client']);
        $provider = User::factory()->create(['user_type' => 'provider']);
        $category = ServiceCategory::create(['name' => 'Plumbing', 'slug' => 'plumbing', 'is_active' => true]);
        
        $job = ServiceListing::create([
            'provider_id' => $client->id,
            'category_id' => $category->id,
            'title' => 'Fix my sink',
            'description' => 'Leaking sink needs urgent repair. Please bring your own tools and parts. This is a very serious issue.',
            'hourly_rate' => 50,
            'service_location' => 'New York',
            'is_available' => true,
            'status' => 'active',
        ]);

        Bid::create([
            'listing_id' => $job->id,
            'provider_id' => $provider->id,
            'amount' => 45,
            'message' => 'I can fix it',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($provider)->getJson('/api/v1/my-bids');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.bids')
            ->assertJsonPath('data.bids.0.amount', "45.00");
    }
}
