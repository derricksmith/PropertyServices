<?php
// Admin Controller for Proximity Management
// packages/Webkul/PropertyServices/src/Http/Controllers/Admin/ProximityController.php

namespace Webkul\PropertyServices\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\PropertyServices\Services\ProximityService;
use Webkul\PropertyServices\Models\Market;

class ProximityController extends Controller
{
    public function __construct(
        protected ProximityService $proximityService
    ) {}

    /**
     * Display proximity multiplier settings.
     */
    public function index(): View
    {
        $config = config('property_services.proximity_multipliers');
        $markets = Market::where('is_active', true)->get();
        
        // Get analytics data
        $analytics = $this->getProximityAnalytics();

        return view('property_services::admin.proximity.index', compact('config', 'markets', 'analytics'));
    }

    /**
     * Update proximity multiplier configuration.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'enabled' => 'boolean',
            'base_radius_km' => 'required|numeric|min:1|max:50',
            'max_radius_km' => 'required|numeric|min:5|max:100',
            'distance_tiers' => 'required|array',
            'distance_tiers.*.min' => 'required|numeric|min:0',
            'distance_tiers.*.max' => 'required|numeric|min:1',
            'distance_tiers.*.multiplier' => 'required|numeric|min:1|max:3',
            'market_adjustments' => 'array',
            'service_type_adjustments' => 'array',
            'time_based_adjustments' => 'array'
        ]);

        // Update configuration file
        $configPath = config_path('property_services.php');
        $currentConfig = include $configPath;
        
        $currentConfig['proximity_multipliers'] = array_merge(
            $currentConfig['proximity_multipliers'],
            $request->only([
                'enabled', 'base_radius_km', 'max_radius_km', 
                'distance_tiers', 'market_adjustments', 
                'service_type_adjustments', 'time_based_adjustments'
            ])
        );

        file_put_contents($configPath, '<?php return ' . var_export($currentConfig, true) . ';');

        // Clear config cache
        \Artisan::call('config:clear');

        session()->flash('success', 'Proximity multiplier settings updated successfully.');

        return redirect()->route('property_services.admin.proximity.index');
    }

    /**
     * Test proximity calculations for a specific scenario.
     */
    public function testCalculation(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'property_lat' => 'required|numeric',
            'property_lng' => 'required|numeric',
            'vendor_lat' => 'required|numeric',
            'vendor_lng' => 'required|numeric',
            'service_type' => 'required|string',
            'requested_datetime' => 'required|date',
            'priority' => 'required|in:standard,urgent,emergency',
            'market_id' => 'required|exists:property_services_markets,id'
        ]);

        // Create mock objects for testing
        $property = new \Webkul\PropertyServices\Models\Property();
        $property->latitude = $request->property_lat;
        $property->longitude = $request->property_lng;
        $property->market = Market::find($request->market_id);

        $vendorLocation = new \Webkul\PropertyServices\Models\VendorLocation();
        $vendorLocation->latitude = $request->vendor_lat;
        $vendorLocation->longitude = $request->vendor_lng;
        $vendorLocation->distance = $this->proximityService->calculateDistance(
            $request->property_lat, $request->property_lng,
            $request->vendor_lat, $request->vendor_lng
        );

        $proximityData = $this->proximityService->calculateProximityMultiplier(
            $property,
            $vendorLocation,
            $request->service_type,
            $request->requested_datetime,
            $request->priority
        );

        return response()->json([
            'success' => true,
            'data' => $proximityData,
            'explanation' => $this->proximityService->getProximityExplanation($proximityData)
        ]);
    }

    /**
     * Get proximity analytics data.
     */
    protected function getProximityAnalytics(): array
    {
        // Get service request data for analysis
        $serviceRequests = \Webkul\PropertyServices\Models\ServiceRequest::with(['property', 'vendor.vendorLocation'])
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        $analytics = [
            'total_requests' => $serviceRequests->count(),
            'distance_distribution' => [],
            'proximity_impact' => [],
            'average_distance' => 0,
            'max_distance' => 0
        ];

        if ($serviceRequests->isEmpty()) {
            return $analytics;
        }

        $distances = [];
        $proximityImpacts = [];

        foreach ($serviceRequests as $request) {
            if ($request->vendor && $request->vendor->vendorLocation && $request->property) {
                $distance = $this->proximityService->calculateDistance(
                    $request->property->latitude,
                    $request->property->longitude,
                    $request->vendor->vendorLocation->latitude,
                    $request->vendor->vendorLocation->longitude
                );

                $distances[] = $distance;

                // Calculate what the proximity multiplier would have been
                $proximityData = $this->proximityService->calculateProximityMultiplier(
                    $request->property,
                    $request->vendor->vendorLocation,
                    $request->service_type,
                    $request->requested_datetime->toISOString(),
                    $request->priority
                );

                $proximityImpacts[] = $proximityData['multiplier'];
            }
        }

        if (!empty($distances)) {
            $analytics['average_distance'] = round(array_sum($distances) / count($distances), 2);
            $analytics['max_distance'] = round(max($distances), 2);
            $analytics['average_proximity_multiplier'] = round(array_sum($proximityImpacts) / count($proximityImpacts), 3);

            // Distance distribution
            $analytics['distance_distribution'] = [
                '0-2km' => count(array_filter($distances, fn($d) => $d <= 2)),
                '2-5km' => count(array_filter($distances, fn($d) => $d > 2 && $d <= 5)),
                '5-10km' => count(array_filter($distances, fn($d) => $d > 5 && $d <= 10)),
                '10-15km' => count(array_filter($distances, fn($d) => $d > 10 && $d <= 15)),
                '15km+' => count(array_filter($distances, fn($d) => $d > 15))
            ];
        }

        return $analytics;
    }
}