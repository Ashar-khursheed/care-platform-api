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
            [
                'name' => 'Child Care',
                'description' => 'Babysitters, nannies, and daycare providers for children of all ages',
                'icon' => '👶',
                'order' => 1,
            ],
            [
                'name' => 'Senior Care',
                'description' => 'Caregivers for elderly and senior citizens, including companionship and assistance',
                'icon' => '👵',
                'order' => 2,
            ],
            [
                'name' => 'Special Needs Care',
                'description' => 'Specialized care for individuals with disabilities or special needs',
                'icon' => '🤝',
                'order' => 3,
            ],
            [
                'name' => 'Pet Care',
                'description' => 'Pet sitting, dog walking, and pet grooming services',
                'icon' => '🐕',
                'order' => 4,
            ],
            [
                'name' => 'Housekeeping',
                'description' => 'House cleaning, laundry, and general housekeeping services',
                'icon' => '🧹',
                'order' => 5,
            ],
            [
                'name' => 'Tutoring',
                'description' => 'Academic tutoring and educational support for students',
                'icon' => '📚',
                'order' => 6,
            ],
            [
                'name' => 'Personal Care',
                'description' => 'Personal assistance, grooming, and daily living support',
                'icon' => '💆',
                'order' => 7,
            ],
            [
                'name' => 'Home Health Care',
                'description' => 'Medical and nursing care provided at home',
                'icon' => '🏥',
                'order' => 8,
            ],
            [
                'name' => 'Companionship',
                'description' => 'Companionship and social interaction for those who need it',
                'icon' => '💬',
                'order' => 9,
            ],
            [
                'name' => 'Errands & Shopping',
                'description' => 'Help with errands, grocery shopping, and transportation',
                'icon' => '🛒',
                'order' => 10,
            ],
        ];

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