<?php

namespace Webkul\PropertyServices\Services;

use Illuminate\Support\Collection;
use Webkul\PropertyServices\Models\Property;
use Webkul\PropertyServices\Models\SellerLocation;
use Webkul\Marketplace\Models\Seller;
use Carbon\Carbon;

class SellerMatchingService
{
    /**
     * Find matching sellers for a service request.
     */
    public function findMatchingSellers(
        Property $property,
        string $serviceType,
        string $requestedDateTime,
        string $priority = 'standard'
    ): Collection {
        // Find sellers within service radius
        $nearbySellers = SellerLocation::findSellersWithinRadius(
            $property->latitude,
            $property->longitude,
            config('property_services.proximity_settings.default_radius', 15)
        );

        // Filter by service type and availability
        $matchedSellers = $nearbySellers->filter(function ($sellerLocation) use ($serviceType, $requestedDateTime, $priority) {
            $seller = $sellerLocation->seller;
            
            // Check service capabilities
            if (!$this->sellerOffersService($seller, $serviceType)) {
                return false;
            }

            // Check emergency availability
            if ($priority === 'emergency' && !$seller->accepts_emergency_requests) {
                return false;
            }

            // Check time availability
            if (!$this->sellerAvailableAt($seller, $requestedDateTime)) {
                return false;
            }

            return true;
        });

        // Calculate match scores and sort
        $scoredSellers = $matchedSellers->map(function ($sellerLocation) use ($serviceType, $priority) {
            $seller = $sellerLocation->seller;
            $score = $this->calculateMatchScore($seller, $sellerLocation->distance, $serviceType, $priority);
            
            return [
                'seller_id' => $seller->id,
                'seller' => $seller,
                'location' => $sellerLocation,
                'match_score' => $score,
                'estimated_arrival' => $this->estimateArrival($sellerLocation->distance),
                'distance' => $sellerLocation->distance
            ];
        });

        return $scoredSellers->sortByDesc('match_score')->take(5)->values();
    }

    /**
     * Check if seller offers specific service.
     */
    private function sellerOffersService(Seller $seller, string $serviceType): bool
    {
        $serviceCategories = $seller->service_categories ?? [];
        return in_array($serviceType, $serviceCategories);
    }

    /**
     * Check if seller is available at requested time.
     */
    private function sellerAvailableAt(Seller $seller, string $requestedDateTime): bool
    {
        $requestTime = Carbon::parse($requestedDateTime);
        $dayOfWeek = $requestTime->dayOfWeek; // 0 = Sunday, 6 = Saturday
        $timeOfDay = $requestTime->format('H:i');

        $schedule = $seller->availability_schedule ?? [];
        
        // If no schedule set, assume available during business hours
        if (empty($schedule)) {
            return $requestTime->hour >= 8 && $requestTime->hour <= 18;
        }

        // Check specific day availability
        $daySchedule = $schedule[$dayOfWeek] ?? null;
        if (!$daySchedule || !$daySchedule['available']) {
            return false;
        }

        // Check time range
        $startTime = $daySchedule['start_time'] ?? '08:00';
        $endTime = $daySchedule['end_time'] ?? '18:00';

        return $timeOfDay >= $startTime && $timeOfDay <= $endTime;
    }

    /**
     * Calculate seller match score.
     */
    private function calculateMatchScore(
        Seller $seller,
        float $distance,
        string $serviceType,
        string $priority
    ): float {
        // Base scoring components
        $ratingScore = ($seller->rating_average / 5) * 30; // Max 30 points
        $distanceScore = max(0, 25 - ($distance * 1.5)); // Max 25 points, -1.5 per km
        $experienceScore = min(25, $seller->completed_jobs / 5); // Max 25 points
        
        // Service-specific bonuses
        $serviceBonus = 0;
        $serviceCategories = $seller->service_categories ?? [];
        if (count(array_intersect($serviceCategories, [$serviceType])) > 0) {
            $serviceBonus = 10;
        }

        // Priority bonuses
        $priorityBonus = 0;
        if ($priority === 'emergency' && $seller->accepts_emergency_requests) {
            $priorityBonus = 10;
        }

        // Availability bonus (if recently active)
        $availabilityBonus = 0;
        if ($seller->sellerLocation && $seller->sellerLocation->last_updated >= now()->subMinutes(30)) {
            $availabilityBonus = 10;
        }

        $totalScore = $ratingScore + $distanceScore + $experienceScore + $serviceBonus + $priorityBonus + $availabilityBonus;

        return round($totalScore, 2);
    }

    /**
     * Estimate arrival time based on distance.
     */
    private function estimateArrival(float $distance): int
    {
        // Assume average speed of 25 km/h in urban areas (accounting for traffic)
        $hours = $distance / 25;
        return max(15, round($hours * 60)); // Minimum 15 minutes
    }
}