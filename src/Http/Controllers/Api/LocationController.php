<?php

// packages/Webkul/PropertyServices/src/Http/Controllers/Api/LocationController.php

namespace Webkul\PropertyServices\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\PropertyServices\Models\VendorLocation;
use Webkul\PropertyServices\Models\Property;
use Webkul\PropertyServices\Models\ServiceRequest;
use Webkul\Marketplace\Models\Seller;

class LocationController extends Controller
{
    /**
     * Update vendor location for real-time tracking.
     */
    public function updateVendorLocation(Request $request): JsonResponse
    {
        $request->validate([
            'vendor_id' => 'required|exists:marketplace_sellers,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'is_available' => 'boolean'
        ]);

        $location = VendorLocation::updateVendorLocation(
            $request->vendor_id,
            $request->latitude,
            $request->longitude,
            $request->is_available ?? true
        );

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully',
            'data' => $location
        ]);
    }

    /**
     * Get nearby vendors for a location.
     */
    public function getNearbyVendors(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50',
            'service_type' => 'nullable|string'
        ]);

        $vendors = VendorLocation::findVendorsWithinRadius(
            $request->latitude,
            $request->longitude,
            $request->radius ?? 10
        );

        // Filter by service type if provided
        if ($request->service_type) {
            $vendors = $vendors->filter(function ($vendorLocation) use ($request) {
                $vendor = $vendorLocation->vendor;
                $serviceCategories = $vendor->service_categories ?? [];
                return in_array($request->service_type, $serviceCategories);
            });
        }

        return response()->json([
            'success' => true,
            'data' => $vendors->load('vendor')
        ]);
    }

    /**
     * Match vendors to a service request.
     */
    public function matchVendorsToService(Request $request): JsonResponse
    {
        $request->validate([
            'property_id' => 'required|exists:property_services_properties,id',
            'service_type' => 'required|string',
            'priority' => 'required|in:standard,urgent,emergency'
        ]);

        $property = Property::findOrFail($request->property_id);
        
        // Find nearby vendors
        $nearbyVendors = VendorLocation::findVendorsWithinRadius(
            $property->latitude,
            $property->longitude,
            15 // 15km radius
        );

        // Filter and rank vendors
        $matchedVendors = $nearbyVendors->map(function ($vendorLocation) use ($request) {
            $vendor = $vendorLocation->vendor;
            $serviceCategories = $vendor->service_categories ?? [];
            
            // Check if vendor offers the service
            if (!in_array($request->service_type, $serviceCategories)) {
                return null;
            }

            // Check emergency availability
            if ($request->priority === 'emergency' && !$vendor->accepts_emergency_requests) {
                return null;
            }

            // Calculate match score
            $score = $this->calculateVendorScore($vendor, $vendorLocation->distance);

            return [
                'vendor_id' => $vendor->id,
                'vendor' => $vendor,
                'location' => $vendorLocation,
                'match_score' => $score,
                'estimated_arrival' => $this->estimateArrival($vendorLocation->distance)
            ];
        })
        ->filter()
        ->sortByDesc('match_score')
        ->take(5)
        ->values();

        return response()->json([
            'success' => true,
            'data' => $matchedVendors
        ]);
    }

    /**
     * Calculate vendor match score based on rating, distance, and availability.
     */
    private function calculateVendorScore($vendor, float $distance): float
    {
        $ratingScore = ($vendor->rating_average / 5) * 40; // Max 40 points
        $distanceScore = max(0, 30 - ($distance * 2)); // Max 30 points, -2 per km
        $experienceScore = min(30, $vendor->completed_jobs / 10); // Max 30 points

        return round($ratingScore + $distanceScore + $experienceScore, 2);
    }

    /**
     * Estimate arrival time based on distance.
     */
    private function estimateArrival(float $distance): int
    {
        // Assume average speed of 30 km/h in urban areas
        $hours = $distance / 30;
        return max(15, round($hours * 60)); // Minimum 15 minutes
    }
}