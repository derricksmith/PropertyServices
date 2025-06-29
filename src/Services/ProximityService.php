<?php

namespace Webkul\PropertyServices\Services;

use Carbon\Carbon;
use Webkul\PropertyServices\Models\Property;
use Webkul\PropertyServices\Models\Market;
use Webkul\PropertyServices\Models\SellerLocation;

class ProximityService
{
    protected array $config;

    public function __construct()
    {
        $this->config = config('property_services.proximity_multipliers');
    }

    /**
     * Calculate proximity multiplier for a service request.
     */
    public function calculateProximityMultiplier(
        Property $property,
        SellerLocation $sellerLocation,
        string $serviceType,
        string $requestedDateTime,
        string $priority = 'standard'
    ): array {
        if (!$this->config['enabled']) {
            return [
                'multiplier' => 1.0,
                'distance_km' => $sellerLocation->distance ?? 0,
                'tier' => 'base',
                'breakdown' => []
            ];
        }

        $distance = $sellerLocation->distance ?? $this->calculateDistance(
            $property->latitude,
            $property->longitude,
            $sellerLocation->latitude,
            $sellerLocation->longitude
        );

        $breakdown = [];
        
        // Base distance multiplier
        $distanceMultiplier = $this->getDistanceMultiplier($distance);
        $breakdown['distance'] = [
            'multiplier' => $distanceMultiplier['multiplier'],
            'tier' => $distanceMultiplier['tier'],
            'distance_km' => $distance
        ];

        // Market adjustment
        $marketAdjustment = $this->getMarketAdjustment($property->market);
        $breakdown['market'] = $marketAdjustment;

        // Service type adjustment
        $serviceAdjustment = $this->getServiceTypeAdjustment($serviceType);
        $breakdown['service_type'] = $serviceAdjustment;

        // Time-based adjustment
        $timeAdjustment = $this->getTimeAdjustment($requestedDateTime);
        $breakdown['time'] = $timeAdjustment;

        // Priority adjustment
        $priorityAdjustment = $this->getPriorityAdjustment($priority);
        $breakdown['priority'] = $priorityAdjustment;

        // Calculate final multiplier
        $finalMultiplier = $distanceMultiplier['multiplier'] *
                          $marketAdjustment['factor'] *
                          $serviceAdjustment['factor'] *
                          $timeAdjustment['factor'] *
                          $priorityAdjustment['factor'];

        return [
            'multiplier' => round($finalMultiplier, 3),
            'distance_km' => $distance,
            'tier' => $distanceMultiplier['tier'],
            'breakdown' => $breakdown,
            'total_increase_percentage' => round(($finalMultiplier - 1) * 100, 1)
        ];
    }

    /**
     * Calculate seller bonus for proximity.
     */
    public function calculateSellerBonus(float $distance, float $serviceAmount): array
    {
        if (!$this->config['seller_incentives']['proximity_bonuses']['enabled']) {
            return ['bonus_amount' => 0, 'bonus_percentage' => 0, 'reason' => 'Proximity bonuses disabled'];
        }

        $bonusTiers = $this->config['seller_incentives']['proximity_bonuses']['bonus_tiers'];
        $bonusPercentage = 0;
        $appliedTier = null;

        // Find the highest applicable bonus tier
        foreach ($bonusTiers as $tier) {
            if ($distance >= $tier['min_distance'] && $tier['bonus_percentage'] > $bonusPercentage) {
                $bonusPercentage = $tier['bonus_percentage'];
                $appliedTier = $tier;
            }
        }

        $bonusAmount = ($serviceAmount * $bonusPercentage) / 100;

        return [
            'bonus_amount' => round($bonusAmount, 2),
            'bonus_percentage' => $bonusPercentage,
            'applied_tier' => $appliedTier,
            'distance_km' => $distance,
            'reason' => $bonusPercentage > 0 ? "Distance bonus for {$distance}km service" : 'No distance bonus applicable'
        ];
    }

    /**
     * Get sellers within proximity limits for a service.
     */
    public function getSellersWithinProximityLimits(
        Property $property,
        string $serviceType,
        array $sellerLocations
    ): array {
        $serviceConfig = $this->config['service_type_adjustments'][$serviceType] ?? null;
        $maxDistance = $serviceConfig['max_distance_km'] ?? $this->config['max_radius_km'];

        return array_filter($sellerLocations, function ($sellerLocation) use ($maxDistance) {
            return $sellerLocation->distance <= $maxDistance;
        });
    }


    /**
     * Get distance-based multiplier.
     */
    protected function getDistanceMultiplier(float $distance): array
    {
        foreach ($this->config['distance_tiers'] as $tier) {
            if ($distance >= $tier['min'] && $distance < $tier['max']) {
                return [
                    'multiplier' => $tier['multiplier'],
                    'tier' => $tier['label'],
                    'range' => "{$tier['min']}-{$tier['max']}km"
                ];
            }
        }

        // If distance exceeds max range, use the highest tier
        $lastTier = end($this->config['distance_tiers']);
        return [
            'multiplier' => $lastTier['multiplier'],
            'tier' => $lastTier['label'],
            'range' => ">{$lastTier['min']}km"
        ];
    }

    /**
     * Get market-specific adjustment.
     */
    protected function getMarketAdjustment(Market $market): array
    {
        $marketKey = strtolower($market->city_name);
        
        foreach ($this->config['market_adjustments'] as $type => $adjustment) {
            if (in_array($marketKey, $adjustment['markets'])) {
                return [
                    'factor' => $adjustment['adjustment_factor'],
                    'type' => $type,
                    'description' => $adjustment['description']
                ];
            }
        }

        // Default to suburban if not found
        return [
            'factor' => 1.0,
            'type' => 'standard',
            'description' => 'Standard market'
        ];
    }

    /**
     * Get service type adjustment.
     */
    protected function getServiceTypeAdjustment(string $serviceType): array
    {
        $adjustment = $this->config['service_type_adjustments'][$serviceType] ?? [
            'multiplier_factor' => 1.0,
            'max_distance_km' => 25
        ];

        return [
            'factor' => $adjustment['multiplier_factor'],
            'max_distance' => $adjustment['max_distance_km'],
            'service_type' => $serviceType
        ];
    }

    /**
     * Get time-based adjustment.
     */
    protected function getTimeAdjustment(string $requestedDateTime): array
    {
        $dateTime = Carbon::parse($requestedDateTime);
        $dayOfWeek = strtolower($dateTime->format('l'));
        $timeString = $dateTime->format('H:i');
        
        $adjustments = [];
        $totalFactor = 1.0;

        // Check peak hours
        if ($this->isTimeInRanges($timeString, $this->config['time_based_adjustments']['peak_hours']['hours'])) {
            $peakMultiplier = $this->config['time_based_adjustments']['peak_hours']['additional_multiplier'];
            $adjustments[] = [
                'type' => 'peak_hours',
                'multiplier' => $peakMultiplier,
                'description' => $this->config['time_based_adjustments']['peak_hours']['description']
            ];
            $totalFactor *= $peakMultiplier;
        }

        // Check off-peak hours
        if ($this->isTimeInRanges($timeString, $this->config['time_based_adjustments']['off_peak']['hours'])) {
            $offPeakMultiplier = $this->config['time_based_adjustments']['off_peak']['additional_multiplier'];
            $adjustments[] = [
                'type' => 'off_peak',
                'multiplier' => $offPeakMultiplier,
                'description' => $this->config['time_based_adjustments']['off_peak']['description']
            ];
            $totalFactor *= $offPeakMultiplier;
        }

        // Check weekend
        if (in_array($dayOfWeek, $this->config['time_based_adjustments']['weekend']['days'])) {
            $weekendMultiplier = $this->config['time_based_adjustments']['weekend']['additional_multiplier'];
            $adjustments[] = [
                'type' => 'weekend',
                'multiplier' => $weekendMultiplier,
                'description' => $this->config['time_based_adjustments']['weekend']['description']
            ];
            $totalFactor *= $weekendMultiplier;
        }

        return [
            'factor' => $totalFactor,
            'adjustments' => $adjustments,
            'datetime' => $dateTime->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Get priority-based adjustment.
     */
    protected function getPriorityAdjustment(string $priority): array
    {
        $priorityMultipliers = config('property_services.priority_multipliers');
        $multiplier = $priorityMultipliers[$priority] ?? 1.0;

        return [
            'factor' => $multiplier,
            'priority' => $priority,
            'description' => ucfirst($priority) . ' priority service'
        ];
    }

    /**
     * Check if time falls within specified ranges.
     */
    protected function isTimeInRanges(string $time, array $ranges): bool
    {
        foreach ($ranges as $range) {
            [$start, $end] = explode('-', $range);
            if ($time >= $start && $time <= $end) {
                return true;
            }
        }
        return false;
    }

    /**
     * Calculate vendor bonus for proximity.
     */
    public function calculateVendorBonus(float $distance, float $serviceAmount): array
    {
        if (!$this->config['vendor_incentives']['proximity_bonuses']['enabled']) {
            return ['bonus_amount' => 0, 'bonus_percentage' => 0, 'reason' => 'Proximity bonuses disabled'];
        }

        $bonusTiers = $this->config['vendor_incentives']['proximity_bonuses']['bonus_tiers'];
        $bonusPercentage = 0;
        $appliedTier = null;

        // Find the highest applicable bonus tier
        foreach ($bonusTiers as $tier) {
            if ($distance >= $tier['min_distance'] && $tier['bonus_percentage'] > $bonusPercentage) {
                $bonusPercentage = $tier['bonus_percentage'];
                $appliedTier = $tier;
            }
        }

        $bonusAmount = ($serviceAmount * $bonusPercentage) / 100;

        return [
            'bonus_amount' => round($bonusAmount, 2),
            'bonus_percentage' => $bonusPercentage,
            'applied_tier' => $appliedTier,
            'distance_km' => $distance,
            'reason' => $bonusPercentage > 0 ? "Distance bonus for {$distance}km service" : 'No distance bonus applicable'
        ];
    }

    /**
     * Calculate coverage incentive bonus.
     */
    public function calculateCoverageBonus(float $serviceRadius, int $vendorCount, float $serviceAmount): array
    {
        if (!$this->config['vendor_incentives']['coverage_incentives']['enabled']) {
            return ['bonus_amount' => 0, 'bonus_percentage' => 0, 'reason' => 'Coverage incentives disabled'];
        }

        $totalBonusPercentage = 0;
        $bonusReasons = [];

        // Large radius bonus
        if ($serviceRadius > 15) {
            $extraKm = $serviceRadius - 15;
            $radiusBonus = $extraKm * $this->config['vendor_incentives']['coverage_incentives']['large_radius_bonus'] * 100;
            $totalBonusPercentage += $radiusBonus;
            $bonusReasons[] = "Large service area bonus: {$radiusBonus}% for {$extraKm}km over 15km";
        }

        // Underserved area bonus
        if ($vendorCount < 3) {
            $underservedBonus = $this->config['vendor_incentives']['coverage_incentives']['underserved_area_bonus'] * 100;
            $totalBonusPercentage += $underservedBonus;
            $bonusReasons[] = "Underserved area bonus: {$underservedBonus}% (only {$vendorCount} vendors)";
        }

        $bonusAmount = ($serviceAmount * $totalBonusPercentage) / 100;

        return [
            'bonus_amount' => round($bonusAmount, 2),
            'bonus_percentage' => round($totalBonusPercentage, 2),
            'service_radius' => $serviceRadius,
            'vendor_count' => $vendorCount,
            'reasons' => $bonusReasons,
            'breakdown' => [
                'large_radius_bonus' => $serviceRadius > 15 ? ($serviceRadius - 15) * $this->config['vendor_incentives']['coverage_incentives']['large_radius_bonus'] * 100 : 0,
                'underserved_bonus' => $vendorCount < 3 ? $this->config['vendor_incentives']['coverage_incentives']['underserved_area_bonus'] * 100 : 0
            ]
        ];
    }

    /**
     * Get vendors within proximity limits for a service.
     */
    public function getVendorsWithinProximityLimits(
        Property $property,
        string $serviceType,
        array $vendorLocations
    ): array {
        $serviceConfig = $this->config['service_type_adjustments'][$serviceType] ?? null;
        $maxDistance = $serviceConfig['max_distance_km'] ?? $this->config['max_radius_km'];

        return array_filter($vendorLocations, function ($vendorLocation) use ($maxDistance) {
            return $vendorLocation->distance <= $maxDistance;
        });
    }

    /**
     * Calculate distance between two points.
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get proximity pricing explanation for customer.
     */
    public function getProximityExplanation(array $proximityData): string
    {
        $parts = [];
        
        if ($proximityData['multiplier'] == 1.0) {
            return "Standard pricing (vendor within base service area)";
        }

        $breakdown = $proximityData['breakdown'];
        
        if ($breakdown['distance']['multiplier'] > 1.0) {
            $parts[] = "Distance surcharge: " . round(($breakdown['distance']['multiplier'] - 1) * 100, 1) . "% for {$proximityData['distance_km']}km";
        }

        if ($breakdown['time']['factor'] > 1.0) {
            $timeAdjustments = array_map(function($adj) {
                return $adj['description'];
            }, $breakdown['time']['adjustments']);
            $parts[] = "Time premium: " . implode(', ', $timeAdjustments);
        }

        if ($breakdown['service_type']['factor'] > 1.0) {
            $parts[] = "Service type adjustment: " . round(($breakdown['service_type']['factor'] - 1) * 100, 1) . "% for " . $breakdown['service_type']['service_type'];
        }

        $totalIncrease = $proximityData['total_increase_percentage'];
        $explanation = implode('; ', $parts);
        
        return "Total {$totalIncrease}% increase: {$explanation}";
    }
}