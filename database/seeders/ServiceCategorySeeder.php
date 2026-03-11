<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class ServiceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Registered Nurse (RN)', 'description' => 'Professional nursing care by a Registered Nurse', 'icon' => '🩺', 'order' => 1],
            ['name' => 'Licensed Practical Nurse (LPN)', 'description' => 'Practical nursing care by a Licensed Practical Nurse', 'icon' => '👩‍⚕️', 'order' => 2],
            ['name' => 'Certified Nursing Assistant (CNA)', 'description' => 'Assistance with daily living activities by a CNA', 'icon' => '🏥', 'order' => 3],
            ['name' => 'Caregiver', 'description' => 'Compassionate care for seniors and individuals in need', 'icon' => '❤️', 'order' => 4],
            ['name' => 'Med Tech (Medication Technician)', 'description' => 'Professional medication administration and tracking', 'icon' => '💊', 'order' => 5],
            ['name' => 'Adult Family Home Staff', 'description' => 'Comprehensive support for Adult Family Home environments', 'icon' => '🏡', 'order' => 6],
            ['name' => 'Group Home Staff', 'description' => 'Support and care for Group Home facilities', 'icon' => '🏘️', 'order' => 7],
            ['name' => 'Daycare Support Staff', 'description' => 'Help and assistance for daycare centers', 'icon' => '🧸', 'order' => 8],
            ['name' => 'Childcare Worker', 'description' => 'Professional child care and development support', 'icon' => '👶', 'order' => 9],
            ['name' => 'Hospital Support Roles', 'description' => 'Various support and assistance roles within hospital settings', 'icon' => '🏥', 'order' => 10],
            ['name' => 'Pet Care Staff / Worker', 'description' => 'Reliable care and support for pets', 'icon' => '🐾', 'order' => 11],
            ['name' => 'Dental Assistant', 'description' => 'Professional dental assistance and patient care', 'icon' => '🦷', 'order' => 12],
            ['name' => 'Home Health Aide', 'description' => 'In-home medical and personal assistance', 'icon' => '🏠', 'order' => 13],
            ['name' => 'Early Childhood Educator', 'description' => 'Educational care for infants and young children', 'icon' => '�', 'order' => 14],
            ['name' => 'Tutoring (In person and/or Online)', 'description' => 'Academic support and tutoring services', 'icon' => '📚', 'order' => 15],
            ['name' => 'Special needs Care', 'description' => 'Specialized care for individuals with unique needs', 'icon' => '🤝', 'order' => 16],
            ['name' => 'Paraprofessional', 'description' => 'Support for educational and therapeutic environments', 'icon' => '📝', 'order' => 17],
            ['name' => 'Personal Care Assistant', 'description' => 'Assistance with personal care and daily routines', 'icon' => '🚿', 'order' => 18],
            ['name' => 'Behavior Support Technician', 'description' => 'Support for behavioral interventions and plans', 'icon' => '🧠', 'order' => 19],
            ['name' => 'Applied Behavioral Analysis (ABA)', 'description' => 'Professional ABA therapy services', 'icon' => '📊', 'order' => 20],
            ['name' => 'Behavioral Health Technician (BHT)', 'description' => 'Support for behavioral health and mental wellness', 'icon' => '🧘', 'order' => 21],
            ['name' => 'Registered Behavior Technician (RBT)', 'description' => 'Certified support for behavioral analysis programs', 'icon' => '📋', 'order' => 22],
            ['name' => 'Housekeeping Services', 'description' => 'Comprehensive housekeeping and home management', 'icon' => '🧹', 'order' => 23],
            ['name' => 'Cleaning Professional', 'description' => 'Professional and deep cleaning services', 'icon' => '✨', 'order' => 24],
            ['name' => 'Companionship', 'description' => 'Friendly companionship and social interaction', 'icon' => '☕', 'order' => 25],
        ];

        // Clear existing to avoid duplicates if re-run
        // Disable foreign key constraints as service_listings may reference these
        Schema::disableForeignKeyConstraints();
        ServiceCategory::truncate();
        Schema::enableForeignKeyConstraints();

        foreach ($categories as $category) {
            ServiceCategory::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
                'icon' => $category['icon'],
                'order' => $category['order'],
                'is_active' => true,
            ]);
        }

        echo "Service categories created successfully!\n";
        echo "Total categories: " . count($categories) . "\n";
    }
}