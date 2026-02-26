<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ServiceCategory;
use Illuminate\Support\Str;

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
            ['name' => 'Dental Assistant', 'description' => 'Professional dental assistance and patient care', 'icon' => '🦷', 'order' => 4],
            ['name' => 'Home Health Aide', 'description' => 'In-home medical and personal assistance', 'icon' => '🏠', 'order' => 5],
            ['name' => 'Early Childhood Educator', 'description' => 'Educational care for infants and young children', 'icon' => '🧸', 'order' => 6],
            ['name' => 'Child Care Teacher/Assistant', 'description' => 'Support and teaching for child care facilities and homes', 'icon' => '🏫', 'order' => 7],
            ['name' => 'Tutoring (In person and/or Online)', 'description' => 'Academic support and tutoring services', 'icon' => '📚', 'order' => 8],
            ['name' => 'Special needs Care', 'description' => 'Specialized care for individuals with unique needs', 'icon' => '🤝', 'order' => 9],
            ['name' => 'Nanny Services', 'description' => 'Dedicated in-home child care and nanny services', 'icon' => '👶', 'order' => 10],
            ['name' => 'Paraprofessional', 'description' => 'Support for educational and therapeutic environments', 'icon' => '📝', 'order' => 11],
            ['name' => 'Personal Care Assistant', 'description' => 'Assistance with personal care and daily routines', 'icon' => 'Shower', 'order' => 12],
            ['name' => 'Behavior Support Technician', 'description' => 'Support for behavioral interventions and plans', 'icon' => '🧠', 'order' => 13],
            ['name' => 'Applied Behavioral Analysis (ABA)', 'description' => 'Professional ABA therapy services', 'icon' => '📊', 'order' => 14],
            ['name' => 'Behavioral Health Technician (BHT)', 'description' => 'Support for behavioral health and mental wellness', 'icon' => '🧘', 'order' => 15],
            ['name' => 'Registered Behavior Technician (RBT)', 'description' => 'Certified support for behavioral analysis programs', 'icon' => '📋', 'order' => 16],
            ['name' => 'Housekeeping Services', 'description' => 'Comprehensive housekeeping and home management', 'icon' => '🧹', 'order' => 17],
            ['name' => 'Cleaning Professional', 'description' => 'Professional and deep cleaning services', 'icon' => '✨', 'order' => 18],
            ['name' => 'Companionship', 'description' => 'Friendly companionship and social interaction', 'icon' => '☕', 'order' => 19],
            ['name' => 'Pet Care Services', 'description' => 'Care, walking, and sitting services for pets', 'icon' => '🐾', 'order' => 20],
        ];

        // Clear existing to avoid duplicates if re-run
        ServiceCategory::truncate();

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