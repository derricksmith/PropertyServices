<?php

namespace Webkul\PropertyServices\Services;

use Webkul\PropertyServices\Models\Property;
use Webkul\PropertyServices\Models\Market;
use Webkul\PropertyServices\Models\VendorLocation;
use Webkul\Marketplace\Models\Seller;

class PricingService
{
    protected ProximityService $proximityService;

    public function __construct(ProximityService $proximityService)
    {
        $this->proximityService = $proximityService;
    }

    /**
     * Calculate service cost with proximity multipliers.
     */
    public function calculateServiceCost(
        Property $property,
        string $serviceType,
        string $priority,
        ?Seller $vendor = null,
        ?string $requestedDateTime = null
    ): array {
        $market = $property->market;
        $serviceConfig = config("property_services.service_types.{$serviceType}");
        $baseDuration = $serviceConfig['base_duration'] ?? 120; // minutes

        // Base cost calculation
        $baseRate = $vendor?->base_hourly_rate ?? $this->getDefaultServiceRate($serviceType, $market);
        $baseCost = ($baseRate * $baseDuration) / 60; // Convert to hours

        // Property size adjustment
        $sizeFactor = $this->calculateSizeFactor($property, $serviceType);
        $adjustedCost = $baseCost * $sizeFactor;

        // Priority multiplier
        $priorityMultiplier = config("property_services.priority_multipliers.{$priority}", 1.0);
        $priorityAdjustedCost = $adjustedCost * $priorityMultiplier;

        // Proximity multipliers (if vendor is specified)
        $proximityData = null;
        $proximityAdjustedCost = $priorityAdjustedCost;
        
        if ($vendor && $requestedDateTime) {
            $vendorLocation = $vendor->vendorLocation;
            if ($vendorLocation) {
                $proximityData = $this->proximityService->calculateProximityMultiplier(
                    $property,
                    $vendorLocation,
                    $serviceType,
                    $requestedDateTime,
                    $priority
                );
                
                $proximityAdjustedCost = $priorityAdjustedCost * $proximityData['multiplier'];
            }
        }

        // Apply minimum service fee
        $finalServiceCost = max($proximityAdjustedCost, $market->min_service_fee);

        // Calculate fees and taxes
        $platformFee = $finalServiceCost * (config('property_services.payment_settings.platform_commission') / 100);
        $processingFee = ($finalServiceCost * (config('property_services.payment_settings.processing_fee') / 100)) + 
                        config('property_services.payment_settings.fixed_fee');
        $taxes = $finalServiceCost * ($market->tax_rate / 100);

        // Calculate vendor bonuses if applicable
        $vendorBonus = null;
        if ($vendor && $proximityData) {
            $vendorBonus = $this->proximityService->calculateVendorBonus(
                $proximityData['distance_km'],
                $finalServiceCost
            );
        }

        // Total cost
        $totalCost = $finalServiceCost + $platformFee + $processingFee + $taxes;

        return [
            'service_cost' => round($finalServiceCost, 2),
            'platform_fee' => round($platformFee, 2),
            'processing_fee' => round($processingFee, 2),
            'taxes' => round($taxes, 2),
            'total_cost' => round($totalCost, 2),
            'currency' => $market->currency_code,
            'proximity_data' => $proximityData,
            'vendor_bonus' => $vendorBonus,
            'breakdown' => [
                'base_rate' => $baseRate,
                'base_duration' => $baseDuration,
                'size_factor' => $sizeFactor,
                'priority_multiplier' => $priorityMultiplier,
                'proximity_multiplier' => $proximityData['multiplier'] ?? 1.0,
                'minimum_fee_applied' => $priorityAdjustedCost < $market->min_service_fee,
                'proximity_explanation' => $proximityData ? $this->proximityService->getProximityExplanation($proximityData) : null
            ]
        ];
    }

    /**
     * Get default service rate for a service type in a market.
     */
    private function getDefaultServiceRate(string $serviceType, Market $market): float
    {
        // Default rates per hour by service type and market
        $defaultRates = [
            'cleaning' => [
                'US' => 35.00,
                'default' => 30.00
            ],
            'maintenance' => [
                'US' => 45.00,
                'default' => 40.00
            ],
            'landscaping' => [
                'US' => 40.00,
                'default' => 35.00
            ],
            'pest-control' => [
                'US' => 50.00,
                'default' => 45.00
            ],
            'plumbing' => [
                'US' => 75.00,
                'default' => 65.00
            ],
            'electrical' => [
                'US' => 80.00,
                'default' => 70.00
            ]
        ];

        $serviceRates = $defaultRates[$serviceType] ?? $defaultRates['cleaning'];
        return $serviceRates[$market->country_code] ?? $serviceRates['default'];
    }

    /**
     * Calculate size factor based on property characteristics.
     */
    private function calculateSizeFactor(Property $property, string $serviceType): float
    {
        // Base factor
        $factor = 1.0;

        // Adjust based on square footage
        if ($property->square_footage) {
            if ($property->square_footage > 2000) {
                $factor += 0.3; // 30% increase for large properties
            } elseif ($property->square_footage > 1500) {
                $factor += 0.2; // 20% increase for medium properties
            } elseif ($property->square_footage < 800) {
                $factor -= 0.1; // 10% discount for small properties
            }
        }

        // Adjust based on bedrooms/bathrooms for cleaning services
        if ($serviceType === 'cleaning') {
            $rooms = ($property->bedrooms ?? 0) + ($property->bathrooms ?? 0);
            if ($rooms > 6) {
                $factor += 0.2;
            } elseif ($rooms > 4) {
                $factor += 0.1;
            }
        }

        // Property type adjustments
        switch ($property->property_type) {
            case 'commercial':
                $factor += 0.25; // 25% increase for commercial properties
                break;
            case 'vacation':
                $factor += 0.1; // 10% increase for vacation rentals (higher standards)
                break;
        }

        return max(0.7, min(2.0, $factor)); // Cap between 70% and 200%
    }
}