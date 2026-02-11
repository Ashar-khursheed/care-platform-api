<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsPageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_published_pages_with_content()
    {
        // Create an author
        $author = User::factory()->create();

        // Create published pages
        Page::create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'content' => 'About content',
            'is_published' => true,
            'published_at' => now(),
            'author_id' => $author->id,
        ]);

        Page::create([
            'title' => 'Terms',
            'slug' => 'terms',
            'content' => 'Terms content',
            'is_published' => true,
            'published_at' => now(),
            'author_id' => $author->id,
        ]);

        // Create an unpublished page
        Page::create([
            'title' => 'Draft',
            'slug' => 'draft',
            'content' => 'Draft content',
            'is_published' => false,
            'author_id' => $author->id,
        ]);

        $response = $this->getJson('/api/v1/cms/pages/all');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.title', 'About Us')
            ->assertJsonPath('data.0.content', 'About content')
            ->assertJsonPath('data.1.title', 'Terms')
            ->assertJsonPath('data.1.content', 'Terms content');
            
        // Ensure draft page is not included
        $slugs = collect($response->json('data'))->pluck('slug')->toArray();
        $this->assertNotContains('draft', $slugs);
    }

    public function test_can_get_single_page_by_slug()
    {
        // Create an author
        $author = User::factory()->create();

        // Create a published page
        Page::create([
            'title' => 'Privacy Policy',
            'slug' => 'privacy-policy',
            'content' => 'Privacy content',
            'is_published' => true,
            'published_at' => now(),
            'author_id' => $author->id,
        ]);

        $response = $this->getJson('/api/v1/cms/pages/privacy-policy');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Privacy Policy')
            ->assertJsonPath('data.content', 'Privacy content')
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id', 'title', 'slug', 'content', 'excerpt', 'featured_image', 'template', 'published_at', 'reading_time', 'author', 'seo'
                ]
            ]);
    }

    public function test_returns_404_for_invalid_slug()
    {
        $response = $this->getJson('/api/v1/cms/pages/non-existent-slug');
        $response->assertStatus(404);
    }
}
