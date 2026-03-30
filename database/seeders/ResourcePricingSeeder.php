<?php

namespace Database\Seeders;

use App\Models\ResourcePricing;
use Illuminate\Database\Seeder;

class ResourcePricingSeeder extends Seeder
{
    public function run(): void
    {
        $resources = [
            [
                'resource_type' => 'base_price',
                'label' => 'Basisprijs',
                'unit' => 'per server',
                'price_per_unit' => 0.00,
                'min_value' => 0,
                'max_value' => 1,
                'step' => 1,
                'default_value' => 1,
            ],
            [
                'resource_type' => 'ram_gb',
                'label' => 'RAM',
                'unit' => 'GB',
                'price_per_unit' => 1.00,
                'min_value' => 1,
                'max_value' => 128,
                'step' => 1,
                'default_value' => 2,
            ],
            [
                'resource_type' => 'cpu_core',
                'label' => 'CPU Cores',
                'unit' => 'vCPU',
                'price_per_unit' => 2.00,
                'min_value' => 1,
                'max_value' => 32,
                'step' => 1,
                'default_value' => 1,
            ],
            [
                'resource_type' => 'storage_gb',
                'label' => 'Opslag',
                'unit' => 'GB',
                'price_per_unit' => 0.05,
                'min_value' => 10,
                'max_value' => 2000,
                'step' => 10,
                'default_value' => 20,
            ],
            [
                'resource_type' => 'ipv4',
                'label' => 'IPv4 Adressen',
                'unit' => 'IP',
                'price_per_unit' => 2.00,
                'min_value' => 1,
                'max_value' => 8,
                'step' => 1,
                'default_value' => 1,
            ],
        ];

        foreach ($resources as $resource) {
            ResourcePricing::updateOrCreate(
                ['resource_type' => $resource['resource_type']],
                $resource
            );
        }
    }
}
