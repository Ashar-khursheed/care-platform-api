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
        Schema::create('seo_settings', function (Blueprint $table) {
            $table->id();
            $table->string('page_type')->unique(); // home, listings, listing_detail, about, contact, etc.
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->string('og_type')->default('website');
            $table->string('twitter_card')->default('summary_large_image');
            $table->text('schema_markup')->nullable(); // JSON-LD structured data
            $table->text('custom_head_scripts')->nullable(); // Custom scripts in <head>
            $table->text('custom_body_scripts')->nullable(); // Custom scripts before </body>
            $table->timestamps();
        });

        // Insert default SEO settings
        $timestamp = now();
        
        DB::table('seo_settings')->insert([
            [
                'page_type' => 'home',
                'meta_title' => 'Care Platform - Find Trusted Care Services Near You',
                'meta_description' => 'Connect with verified care providers for childcare, senior care, pet care, housekeeping, and more. Book trusted professionals in your area.',
                'meta_keywords' => 'care services, babysitter, childcare, senior care, pet care, housekeeping, home services',
                'og_title' => 'Care Platform - Find Trusted Care Services',
                'og_description' => 'Connect with verified care providers for childcare, senior care, pet care, and more.',
                'og_image' => null,
                'og_type' => 'website',
                'twitter_card' => 'summary_large_image',
                'schema_markup' => null,
                'custom_head_scripts' => null,
                'custom_body_scripts' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'page_type' => 'listings',
                'meta_title' => 'Browse Care Services - Care Platform',
                'meta_description' => 'Browse and book trusted care providers in your area. Filter by service type, location, rating, and availability.',
                'meta_keywords' => 'care services, find caregiver, book services, local providers',
                'og_title' => 'Browse Care Services',
                'og_description' => 'Browse and book trusted care providers in your area.',
                'og_image' => null,
                'og_type' => 'website',
                'twitter_card' => 'summary_large_image',
                'schema_markup' => null,
                'custom_head_scripts' => null,
                'custom_body_scripts' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'page_type' => 'about',
                'meta_title' => 'About Us - Care Platform',
                'meta_description' => 'Learn about Care Platform, our mission to connect families with trusted care providers, and how we ensure safety and quality.',
                'meta_keywords' => 'about care platform, our mission, care services',
                'og_title' => 'About Care Platform',
                'og_description' => 'Learn about our mission to connect families with trusted care providers.',
                'og_image' => null,
                'og_type' => 'website',
                'twitter_card' => 'summary_large_image',
                'schema_markup' => null,
                'custom_head_scripts' => null,
                'custom_body_scripts' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'page_type' => 'contact',
                'meta_title' => 'Contact Us - Care Platform',
                'meta_description' => 'Get in touch with Care Platform. Contact our support team for help with bookings, provider questions, or general inquiries.',
                'meta_keywords' => 'contact care platform, customer support, help',
                'og_title' => 'Contact Care Platform',
                'og_description' => 'Get in touch with our support team for help with bookings and inquiries.',
                'og_image' => null,
                'og_type' => 'website',
                'twitter_card' => 'summary_large_image',
                'schema_markup' => null,
                'custom_head_scripts' => null,
                'custom_body_scripts' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_settings');
    }
};