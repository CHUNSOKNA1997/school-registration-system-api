<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@school.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'is_active' => true,
            'phone' => '012345678',
        ]);

        // Create Staff User
        User::create([
            'name' => 'Staff User',
            'email' => 'staff@school.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'is_active' => true,
            'phone' => '012345679',
        ]);

        // Create additional test users
        User::create([
            'name' => 'John Doe',
            'email' => 'john@school.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
            'is_active' => true,
            'phone' => '012345680',
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@school.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'is_active' => true,
            'phone' => '012345681',
        ]);
    }
}
