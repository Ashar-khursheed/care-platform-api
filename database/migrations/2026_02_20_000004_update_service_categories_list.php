<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clear existing categories
        // We use DB::table to avoid model events/observers issues during migration
        // Disable foreign key constraints to allow truncating even if referenced by service_listings
        Schema::disableForeignKeyConstraints();
        DB::table('service_categories')->truncate();
        Schema::enableForeignKeyConstraints();

        // 2. Define the new list of categories
        $categories = [
            'Registered Nurse (RN)' => '🩺',
            'Licensed Practical Nurse (LPN)' => '👩‍⚕️',
            'Certified Nursing Assistant (CNA)' => '🏥',
            'Dental Assistant' => '🦷',
            'Home Health Aide' => '🏠',
            'Early Childhood Educator' => '🧸',
            'Child Care Teacher/Assistant' => '🏫',
            'Tutoring (In person and/or Online)' => '📚',
            'Special needs Care' => '🤝',
            'Nanny Services' => '👶',
            'Paraprofessional' => '📝',
            'Personal Care Assistant' => '🚿',
            'Behavior Support Technician' => '🧠',
            'Applied Behavioral Analysis (ABA)' => '📊',
            'Behavioral Health Technician (BHT)' => '🧘',
            'Registered Behavior Technician (RBT)' => '📋',
            'Housekeeping Services' => '🧹',
            'Cleaning Professional' => '✨',
            'Companionship' => '☕',
            'Pet Care Services' => '🐾',
        ];

        // 3. Insert new categories
        $now = now();
        $order = 1;

        foreach ($categories as $name => $icon) {
            DB::table('service_categories')->insert([
                'name' => $name,
                'slug' => Str::slug($name),
                'icon' => $icon,
                'description' => $name, // Using name as description for now
                'order' => $order++,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We will just clear them.
        Schema::disableForeignKeyConstraints();
        DB::table('service_categories')->truncate();
        Schema::enableForeignKeyConstraints();
    }
};
