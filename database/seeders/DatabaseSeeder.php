<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Package;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::create([
            'name' => 'Cloudito Admin',
            'email' => 'admin@cloudito.nl',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        // Demo packages
        $packages = [
            [
                'virtfusion_package_id' => 1,
                'name' => 'Starter',
                'category' => 'vps',
                'memory' => 1024,
                'storage' => 20,
                'cpu_cores' => 1,
                'traffic' => 2000,
                'price_monthly' => 4.99,
                'price_quarterly' => 13.99,
                'price_yearly' => 49.99,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'virtfusion_package_id' => 2,
                'name' => 'Professional',
                'category' => 'vps',
                'memory' => 4096,
                'storage' => 60,
                'cpu_cores' => 2,
                'traffic' => 4000,
                'price_monthly' => 9.99,
                'price_quarterly' => 27.99,
                'price_yearly' => 99.99,
                'is_active' => true,
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'virtfusion_package_id' => 3,
                'name' => 'Enterprise',
                'category' => 'vps',
                'memory' => 8192,
                'storage' => 120,
                'cpu_cores' => 4,
                'traffic' => 10000,
                'price_monthly' => 24.99,
                'price_quarterly' => 69.99,
                'price_yearly' => 249.99,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'virtfusion_package_id' => 4,
                'name' => 'Ultimate',
                'category' => 'vps',
                'memory' => 16384,
                'storage' => 200,
                'cpu_cores' => 8,
                'traffic' => 0,
                'price_monthly' => 49.99,
                'price_quarterly' => 139.99,
                'price_yearly' => 499.99,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($packages as $pkg) {
            Package::create($pkg);
        }
    }
}
