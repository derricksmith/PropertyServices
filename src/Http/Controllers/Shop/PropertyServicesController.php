<?php
// Homepage Controller for PropertyServices
// packages/Webkul/PropertyServices/src/Http/Controllers/Shop/PropertyServicesController.php

namespace Webkul\PropertyServices\Http\Controllers\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\PropertyServices\Models\Property;
use Webkul\PropertyServices\Models\Market;
use Webkul\PropertyServices\Models\SellerLocation;
use Webkul\PropertyServices\Services\SellerMatchingService;
use Webkul\Marketplace\Models\Seller;

class PropertyServicesController extends Controller
{
    public function __construct(
        protected SellerMatchingService $sellerMatching
    ) {}

    /**
     * Display the PropertyServices homepage with map.
     */
    public function index(): View
    {
        // Check if customer is logged in
        if (!auth('customer')->check()) {
            return redirect()->route('customer.session.index')
                ->with('info', 'Please log in to view available service providers in your area.');
        }

        $customer = auth('customer')->user();
        
        // Get customer's properties
        $properties = Property::where('customer_id', $customer->id)
            ->where('is_active', true)
            ->with('market')
            ->orderBy('name')
            ->get();

        // Get available service types
        $serviceTypes = config('property_services.service_types');

        // Get active markets for map bounds
        $activeMarkets = Market::where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        // If customer has properties, get initial sellers for first property
        $initialSellers = collect();
        $selectedProperty = null;
        
        if ($properties->isNotEmpty()) {
            $selectedProperty = $properties->first();
            $initialSellers = $this->getSellersNearProperty($selectedProperty, 'cleaning');
        }

        return view('property_services::shop.home.index', compact(
            'customer',
            'properties', 
            'serviceTypes',
            'activeMarkets',
            'initialSellers',
            'selectedProperty'
        ));
    }

    /**
     * Get sellers near a property via AJAX.
     */
    public function getNearbySeliers(Request $request): JsonResponse
    {
        if (!auth('customer')->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $request->validate([
            'property_id' => 'required|exists:property_services_properties,id',
            'service_type' => 'required|string',
            'radius' => 'nullable|numeric|min:1|max:50'
        ]);

        $property = Property::where('id', $request->property_id)
            ->where('customer_id', auth('customer')->id())
            ->firstOrFail();

        $radius = $request->radius ?? 15; // Default 15km
        $serviceType = $request->service_type;

        $sellers = $this->getSellersNearProperty($property, $serviceType, $radius);

        return response()->json([
            'success' => true,
            'property' => [
                'id' => $property->id,
                'name' => $property->name,
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
                'address' => $property->address
            ],
            'sellers' => $sellers->map(function ($seller) {
                return [
                    'id' => $seller['seller']->id,
                    'name' => $seller['seller']->shop_title,
                    'latitude' => $seller['location']->latitude,
                    'longitude' => $seller['location']->longitude,
                    'distance' => round($seller['distance'], 1),
                    'rating' => $seller['seller']->rating_average,
                    'total_reviews' => $seller['seller']->total_reviews,
                    'completed_jobs' => $seller['seller']->completed_jobs,
                    'is_available' => $seller['location']->is_available,
                    'service_categories' => $seller['seller']->service_categories,
                    'base_hourly_rate' => $seller['seller']->base_hourly_rate,
                    'match_score' => $seller['match_score'] ?? 0,
                    'estimated_arrival' => $this->estimateArrival($seller['distance']),
                    'accepts_emergency' => $seller['seller']->accepts_emergency_requests ?? false
                ];
            })
        ]);
    }

    /**
     * Get service types for AJAX requests.
     */
    public function getServiceTypes(): JsonResponse
    {
        $serviceTypes = config('property_services.service_types');
        
        return response()->json([
            'success' => true,
            'service_types' => collect($serviceTypes)->map(function ($config, $key) {
                return [
                    'key' => $key,
                    'name' => $config['name'],
                    'icon' => $config['icon'] ?? 'tool',
                    'category' => $config['category'] ?? 'general',
                    'requires_access' => $config['requires_access'] ?? false
                ];
            })->values()
        ]);
    }

    /**
     * Start booking process from homepage.
     */
    public function startBooking(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'property_id' => 'required|exists:property_services_properties,id',
            'service_type' => 'required|string',
            'seller_id' => 'nullable|exists:marketplace_sellers,id'
        ]);

        $property = Property::where('id', $request->property_id)
            ->where('customer_id', auth('customer')->id())
            ->firstOrFail();

        $bookingData = [
            'property_id' => $property->id,
            'service_type' => $request->service_type,
        ];

        if ($request->seller_id) {
            $bookingData['preferred_seller_id'] = $request->seller_id;
        }

        return redirect()->route('shop.customer.booking.datetime', $bookingData);
    }

    /**
     * Get property details for map focus.
     */
    public function getPropertyDetails(Request $request): JsonResponse
    {
        if (!auth('customer')->check()) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $request->validate([
            'property_id' => 'required|exists:property_services_properties,id'
        ]);

        $property = Property::where('id', $request->property_id)
            ->where('customer_id', auth('customer')->id())
            ->with('market')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'property' => [
                'id' => $property->id,
                'name' => $property->name,
                'address' => $property->address,
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
                'property_type' => $property->property_type,
                'bedrooms' => $property->bedrooms,
                'bathrooms' => $property->bathrooms,
                'max_occupancy' => $property->max_occupancy,
                'market' => [
                    'name' => $property->market->city_name,
                    'timezone' => $property->market->timezone
                ]
            ]
        ]);
    }

    /**
     * Get sellers near a property.
     */
    protected function getSellersNearProperty(Property $property, string $serviceType, float $radius = 15): \Illuminate\Support\Collection
    {
        // Find sellers within radius
        $nearbySellers = SellerLocation::findSellersWithinRadius(
            $property->latitude,
            $property->longitude,
            $radius
        );

        // Filter by service type and availability
        $filteredSellers = $nearbySellers->filter(function ($sellerLocation) use ($serviceType) {
            $seller = $sellerLocation->seller;
            
            if (!$seller || !$seller->is_approved) {
                return false;
            }

            // Check if seller offers this service
            $serviceCategories = $seller->service_categories ?? [];
            return in_array($serviceType, $serviceCategories);
        });

        // Add match scoring
        return $filteredSellers->map(function ($sellerLocation) use ($serviceType) {
            $seller = $sellerLocation->seller;
            
            return [
                'seller' => $seller,
                'location' => $sellerLocation,
                'distance' => $sellerLocation->distance,
                'match_score' => $this->calculateBasicMatchScore($seller, $sellerLocation->distance)
            ];
        })->sortByDesc('match_score')->take(20)->values();
    }

    /**
     * Calculate basic match score for homepage display.
     */
    protected function calculateBasicMatchScore($seller, float $distance): float
    {
        $ratingScore = ($seller->rating_average / 5) * 40; // Max 40 points
        $distanceScore = max(0, 30 - ($distance * 2)); // Max 30 points, -2 per km
        $experienceScore = min(30, $seller->completed_jobs / 10); // Max 30 points

        return round($ratingScore + $distanceScore + $experienceScore, 2);
    }

    /**
     * Estimate arrival time.
     */
    protected function estimateArrival(float $distance): int
    {
        $hours = $distance / 25; // 25 km/h average in urban areas
        return max(15, round($hours * 60)); // Minimum 15 minutes
    }
}
