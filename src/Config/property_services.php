<?php
// packages/Webkul/PropertyServices/src/Config/property_services.php

return [
    'service_types' => [
        'cleaning' => [
            'name' => 'House Cleaning',
            'icon' => 'cleaning-icon',
            'category' => 'maintenance',
            'base_duration' => 120, // minutes
            'requires_access' => true,
        ],
        'maintenance' => [
            'name' => 'General Maintenance',
            'icon' => 'maintenance-icon',
            'category' => 'maintenance',
            'base_duration' => 180,
            'requires_access' => true,
        ],
        'landscaping' => [
            'name' => 'Landscaping',
            'icon' => 'landscaping-icon',
            'category' => 'outdoor',
            'base_duration' => 240,
            'requires_access' => false,
        ],
        'pest-control' => [
            'name' => 'Pest Control',
            'icon' => 'pest-control-icon',
            'category' => 'maintenance',
            'base_duration' => 90,
            'requires_access' => true,
        ],
        'plumbing' => [
            'name' => 'Plumbing Services',
            'icon' => 'plumbing-icon',
            'category' => 'emergency',
            'base_duration' => 120,
            'requires_access' => true,
        ],
        'electrical' => [
            'name' => 'Electrical Services',
            'icon' => 'electrical-icon',
            'category' => 'emergency',
            'base_duration' => 150,
            'requires_access' => true,
        ],
    ],

    'priority_multipliers' => [
        'standard' => 1.0,
        'urgent' => 1.25,
        'emergency' => 1.50,
    ],

    'proximity_settings' => [
        'default_radius' => 15, // km
        'max_radius' => 50, // km
        'min_vendors_for_matching' => 3,
    ],

     'proximity_multipliers' => [
        'enabled' => true,
        'base_radius_km' => 5, // Base radius for no multiplier
        'max_radius_km' => 25, // Maximum service radius
        
        'distance_tiers' => [
            // Distance ranges and their corresponding multipliers
            ['min' => 0, 'max' => 2, 'multiplier' => 1.0, 'label' => 'Very Close'],      // 0-2km: no extra charge
            ['min' => 2, 'max' => 5, 'multiplier' => 1.1, 'label' => 'Close'],         // 2-5km: 10% increase
            ['min' => 5, 'max' => 10, 'multiplier' => 1.25, 'label' => 'Nearby'],       // 5-10km: 25% increase
            ['min' => 10, 'max' => 15, 'multiplier' => 1.4, 'label' => 'Moderate'],     // 10-15km: 40% increase
            ['min' => 15, 'max' => 20, 'multiplier' => 1.6, 'label' => 'Far'],          // 15-20km: 60% increase
            ['min' => 20, 'max' => 25, 'multiplier' => 1.8, 'label' => 'Very Far'],     // 20-25km: 80% increase
        ],
        
        'market_adjustments' => [
            // Market-specific adjustments to proximity multipliers
            'dense_urban' => [
                'description' => 'Dense urban areas (high vendor density)',
                'adjustment_factor' => 0.8, // Reduce multipliers by 20%
                'markets' => ['new_york', 'san_francisco', 'boston']
            ],
            'suburban' => [
                'description' => 'Suburban areas (medium vendor density)',
                'adjustment_factor' => 1.0, // Standard multipliers
                'markets' => ['austin', 'nashville', 'denver']
            ],
            'rural' => [
                'description' => 'Rural areas (low vendor density)',
                'adjustment_factor' => 1.3, // Increase multipliers by 30%
                'markets' => ['rural_areas']
            ]
        ],
        
        'service_type_adjustments' => [
            // Different service types may have different proximity sensitivity
            'emergency' => [
                'multiplier_factor' => 1.5, // Emergency services cost more for distance
                'max_distance_km' => 15 // Shorter max distance for emergencies
            ],
            'cleaning' => [
                'multiplier_factor' => 1.0, // Standard proximity pricing
                'max_distance_km' => 25
            ],
            'maintenance' => [
                'multiplier_factor' => 1.2, // Maintenance tools/equipment make distance costly
                'max_distance_km' => 20
            ],
            'landscaping' => [
                'multiplier_factor' => 1.3, // Heavy equipment transport costs
                'max_distance_km' => 15
            ]
        ],
        
        'time_based_adjustments' => [
            // Adjust proximity multipliers based on time of day/demand
            'peak_hours' => [
                'hours' => ['08:00-10:00', '17:00-19:00'], // Morning and evening peaks
                'additional_multiplier' => 1.2, // 20% extra during peak times
                'description' => 'High demand periods'
            ],
            'off_peak' => [
                'hours' => ['22:00-06:00'], // Late night/early morning
                'additional_multiplier' => 1.4, // 40% extra for inconvenient times
                'description' => 'Off-hours premium'
            ],
            'weekend' => [
                'days' => ['saturday', 'sunday'],
                'additional_multiplier' => 1.15, // 15% weekend premium
                'description' => 'Weekend service premium'
            ]
        ]
    ],

    'seller_incentives' => [
        'proximity_bonuses' => [
            // Bonus payments to sellers for serving distant properties
            'enabled' => true,
            'bonus_tiers' => [
                ['min_distance' => 10, 'bonus_percentage' => 5], // 5% bonus for 10km+
                ['min_distance' => 15, 'bonus_percentage' => 10], // 10% bonus for 15km+
                ['min_distance' => 20, 'bonus_percentage' => 15], // 15% bonus for 20km+
            ]
        ],
        'coverage_incentives' => [
            // Incentivize sellers to expand their service areas
            'enabled' => true,
            'large_radius_bonus' => 0.02, // 2% bonus per km of service radius over 15km
            'underserved_area_bonus' => 0.2 // 20% bonus for serving areas with <3 sellers
        ]
    ],

    'payment_settings' => [
        'platform_commission' => 15.0, // percentage
        'processing_fee' => 2.9, // percentage
        'fixed_fee' => 0.30, // USD
    ],

    'notification_settings' => [
        'vendor_response_timeout' => 300, // seconds (5 minutes)
        'service_reminder_hours' => 2,
        'completion_review_hours' => 24,
    ],

    'google' => [
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],
];
