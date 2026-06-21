<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'role' => 'customer',
        ]);

        User::factory()->create([
            'name' => 'Test Technician',
            'email' => 'technician@example.com',
            'role' => 'technician',
        ]);

        $categories = [
            ['name' => 'Hardware Repair', 'description' => 'Physical component repairs and replacements'],
            ['name' => 'Software Troubleshooting', 'description' => 'OS issues, malware removal, and software fixes'],
            ['name' => 'Data Recovery', 'description' => 'Recovering lost or corrupted data'],
            ['name' => 'Network Setup', 'description' => 'Wi-Fi, router, and connectivity issues'],
        ];

        foreach ($categories as $category) {
            ServiceCategory::create($category);
        }
    }
}
