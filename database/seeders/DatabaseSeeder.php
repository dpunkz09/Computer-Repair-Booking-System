<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test Customer',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'customer',
            ]
        );

        User::updateOrCreate(
            ['email' => 'technician@example.com'],
            [
                'name' => 'Test Technician',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role' => 'technician',
            ]
        );

        $categories = [
            ['name' => 'Hardware Repair', 'description' => 'Physical component repairs and replacements'],
            ['name' => 'Software Troubleshooting', 'description' => 'OS issues, malware removal, and software fixes'],
            ['name' => 'Data Recovery', 'description' => 'Recovering lost or corrupted data'],
            ['name' => 'Network Setup', 'description' => 'Wi-Fi, router, and connectivity issues'],
        ];

        foreach ($categories as $category) {
            ServiceCategory::updateOrCreate(
                ['name' => $category['name']],
                ['description' => $category['description']]
            );
        }
    }
}
