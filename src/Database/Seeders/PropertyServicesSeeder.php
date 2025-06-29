<?php

namespace Webkul\PropertyServices\Database\Seeders;

use Illuminate\Database\Seeder;
use Webkul\PropertyServices\Models\Market;

class PropertyServicesSeeder extends Seeder
{
    public function run()
    {
        // Create initial US markets
        $markets = [
            [
                'country_code' => 'US',
                'region_code' => 'TX',
                'city_name' => 'Austin',
                'currency_code' => 'USD',
                'timezone' => 'America/Chicago',
                'is_active' => true,
                'launch_date' => now()->format('Y-m-d'),
                'commission_rate' => 15.00,
                'tax_rate' => 8.25,
                'min_service_fee' => 25.00,
                'emergency_multiplier' => 1.50
            ],
            [
                'country_code' => 'US',
                'region_code' => 'TN',
                'city_name' => 'Nashville',
                'currency_code' => 'USD',
                'timezone' => 'America/Chicago',
                'is_active' => true,
                'launch_date' => now()->format('Y-m-d'),
                'commission_rate' => 15.00,
                'tax_rate' => 9.25,
                'min_service_fee' => 25.00,
                'emergency_multiplier' => 1.50
            ],
            [
                'country_code' => 'US',
                'region_code' => 'CO',
                'city_name' => 'Denver',
                'currency_code' => 'USD',
                'timezone' => 'America/Denver',
                'is_active' => true,
                'launch_date' => now()->format('Y-m-d'),
                'commission_rate' => 15.00,
                'tax_rate' => 7.65,
                'min_service_fee' => 30.00,
                'emergency_multiplier' => 1.50
            ]
        ];

        foreach ($markets as $market) {
            Market::create($market);
        }
    }
};