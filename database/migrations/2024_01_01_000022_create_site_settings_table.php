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
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('text'); // text, textarea, image, json, boolean
            $table->string('group')->default('general'); // general, header, footer, social, contact
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('site_settings')->insert([
            // General Settings
            [
                'key' => 'site_name',
                'value' => 'Care Platform',
                'type' => 'text',
                'group' => 'general',
                'description' => 'Website name',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_logo',
                'value' => null,
                'type' => 'image',
                'group' => 'general',
                'description' => 'Main logo',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_favicon',
                'value' => null,
                'type' => 'image',
                'group' => 'general',
                'description' => 'Website favicon',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'site_tagline',
                'value' => 'Find trusted care services near you',
                'type' => 'text',
                'group' => 'general',
                'description' => 'Website tagline',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Header Settings
            [
                'key' => 'header_style',
                'value' => 'default',
                'type' => 'text',
                'group' => 'header',
                'description' => 'Header style: default, transparent, sticky',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'header_menu',
                'value' => json_encode([
                    ['label' => 'Home', 'url' => '/', 'order' => 1],
                    ['label' => 'Find Services', 'url' => '/services', 'order' => 2],
                    ['label' => 'Become a Provider', 'url' => '/provider', 'order' => 3],
                    ['label' => 'About Us', 'url' => '/about', 'order' => 4],
                    ['label' => 'Contact', 'url' => '/contact', 'order' => 5],
                ]),
                'type' => 'json',
                'group' => 'header',
                'description' => 'Header navigation menu',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'show_announcement_bar',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'header',
                'description' => 'Show announcement bar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Footer Settings
            [
                'key' => 'footer_about',
                'value' => 'Care Platform connects families with trusted care providers for childcare, senior care, pet care, and more.',
                'type' => 'textarea',
                'group' => 'footer',
                'description' => 'Footer about text',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'footer_copyright',
                'value' => '© 2024 Care Platform. All rights reserved.',
                'type' => 'text',
                'group' => 'footer',
                'description' => 'Footer copyright text',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'footer_links',
                'value' => json_encode([
                    'Quick Links' => [
                        ['label' => 'About Us', 'url' => '/about'],
                        ['label' => 'How It Works', 'url' => '/how-it-works'],
                        ['label' => 'Safety', 'url' => '/safety'],
                        ['label' => 'Blog', 'url' => '/blog'],
                    ],
                    'For Providers' => [
                        ['label' => 'Become a Provider', 'url' => '/provider/register'],
                        ['label' => 'Provider Resources', 'url' => '/provider/resources'],
                        ['label' => 'Pricing', 'url' => '/pricing'],
                    ],
                    'Support' => [
                        ['label' => 'Help Center', 'url' => '/help'],
                        ['label' => 'Contact Us', 'url' => '/contact'],
                        ['label' => 'FAQs', 'url' => '/faqs'],
                    ],
                ]),
                'type' => 'json',
                'group' => 'footer',
                'description' => 'Footer navigation links',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Social Media
            [
                'key' => 'social_facebook',
                'value' => 'https://facebook.com/careplatform',
                'type' => 'text',
                'group' => 'social',
                'description' => 'Facebook URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'social_twitter',
                'value' => 'https://twitter.com/careplatform',
                'type' => 'text',
                'group' => 'social',
                'description' => 'Twitter URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'social_instagram',
                'value' => 'https://instagram.com/careplatform',
                'type' => 'text',
                'group' => 'social',
                'description' => 'Instagram URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'social_linkedin',
                'value' => 'https://linkedin.com/company/careplatform',
                'type' => 'text',
                'group' => 'social',
                'description' => 'LinkedIn URL',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
            // Contact Information
            [
                'key' => 'contact_email',
                'value' => 'support@careplatform.com',
                'type' => 'text',
                'group' => 'contact',
                'description' => 'Contact email',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_phone',
                'value' => '+1 (555) 123-4567',
                'type' => 'text',
                'group' => 'contact',
                'description' => 'Contact phone',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'contact_address',
                'value' => '123 Main Street, New York, NY 10001',
                'type' => 'textarea',
                'group' => 'contact',
                'description' => 'Contact address',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};