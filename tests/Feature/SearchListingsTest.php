<?php

namespace Tests\Feature;

use App\Models\ServiceListing;
use App\Models\User;
use App\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SearchListingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_search_listings_by_location()
    {
        // Create user and category
        $user = User::factory()->create();
        $category = ServiceCategory::create([
            'name' => 'Plumbing',
            'slug' => 'plumbing',
            'is_active' => true,
        ]);
        
        // Create a listing with specific location
        $listing1 = ServiceListing::create([
            'provider_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Plumber in New York',
            'description' => 'Experienced plumber',
            'hourly_rate' => 50,
            'service_location' => 'New York, NY', // Matching location
            'is_available' => true,
            'status' => 'active',
        ]);

        // Create another listing with different location
        $listing2 = ServiceListing::create([
            'provider_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Plumber in Los Angeles',
            'description' => 'Experienced plumber',
            'hourly_rate' => 60,
            'service_location' => 'Los Angeles, CA', // Non-matching location
            'is_available' => true,
            'status' => 'active',
        ]);

        // Search for 'New York'
        $response = $this->get('/api/v1/listings?location=New York');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.listings')
            ->assertJsonFragment(['title' => 'Plumber in New York']);
            
        // Search for 'Los Angeles'
        $response = $this->get('/api/v1/listings?location=Los Angeles');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.listings')
            ->assertJsonFragment(['title' => 'Plumber in Los Angeles']);
    }
}
