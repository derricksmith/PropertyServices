<?php

namespace Webkul\PropertyServices\Http\Controllers\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\PropertyServices\Models\Property;
use Webkul\PropertyServices\Models\ServiceRequest;
use Webkul\PropertyServices\Models\Market;
use Webkul\PropertyServices\Models\VendorLocation;
use Webkul\PropertyServices\Services\VendorMatchingService;
use Webkul\PropertyServices\Services\PricingService;

class BookingController extends Controller
{
    public function __construct(
        protected VendorMatchingService $vendorMatching,
        protected PricingService $pricing
    ) {}

    /**
     * Step 1: Select service type.
     */
    public function selectServiceType(): View
    {
        $serviceTypes = config('property_services.service_types');
        
        return view('property_services::shop.booking.service-type', compact('serviceTypes'));
    }

    /**
     * Step 2: Select property.
     */
    public function selectProperty(Request $request): View
    {
        $request->validate([
            'service_type' => 'required|string'
        ]);

        $properties = Property::where('customer_id', auth('customer')->id())
            ->where('is_active', true)
            ->with('market')
            ->get();

        $serviceType = $request->service_type;

        return view('property_services::shop.booking.property', compact('properties', 'serviceType'));
    }

    /**
     * Step 3: Select date and time.
     */
    public function selectDateTime(Request $request): View
    {
        $request->validate([
            'service_type' => 'required|string',
            'property_id' => 'required|exists:property_services_properties,id'
        ]);

        $property = Property::where('id', $request->property_id)
            ->where('customer_id', auth('customer')->id())
            ->firstOrFail();

        $serviceType = $request->service_type;
        $serviceConfig = config("property_services.service_types.{$serviceType}");

        // Get available time slots for the next 7 days
        $availableSlots = $this->getAvailableTimeSlots($property, $serviceType);

        return view('property_services::shop.booking.datetime', compact(
            'property', 'serviceType', 'serviceConfig', 'availableSlots'
        ));
    }

    /**
     * Step 4: Select vendor from matched providers.
     */
    public function selectVendor(Request $request): View
    {
        $request->validate([
            'service_type' => 'required|string',
            'property_id' => 'required|exists:property_services_properties,id',
            'requested_datetime' => 'required|date|after:now',
            'priority' => 'required|in:standard,urgent,emergency'
        ]);

        $property = Property::where('id', $request->property_id)
            ->where('customer_id', auth('customer')->id())
            ->firstOrFail();

        // Find matched vendors
        $matchedVendors = $this->vendorMatching->findMatchingVendors(
            $property,
            $request->service_type,
            $request->requested_datetime,
            $request->priority
        );

        // Calculate pricing for each vendor
        $vendorsWithPricing = $matchedVendors->map(function ($vendor) use ($request, $property) {
            $pricing = $this->pricing->calculateServiceCost(
                $property,
                $request->service_type,
                $request->priority,
                $vendor
            );
            
            $vendor['pricing'] = $pricing;
            return $vendor;
        });

        $bookingData = $request->only(['service_type', 'property_id', 'requested_datetime', 'priority']);

        return view('property_services::shop.booking.vendors', compact(
            'property', 'vendorsWithPricing', 'bookingData'
        ));
    }

    /**
     * Step 5: Review booking details.
     */
    public function review(Request $request): View
    {
        $request->validate([
            'service_type' => 'required|string',
            'property_id' => 'required|exists:property_services_properties,id',
            'requested_datetime' => 'required|date|after:now',
            'priority' => 'required|in:standard,urgent,emergency',
            'vendor_id' => 'required|exists:marketplace_sellers,id',
            'special_requirements' => 'nullable|string|max:1000'
        ]);

        $property = Property::where('id', $request->property_id)
            ->where('customer_id', auth('customer')->id())
            ->with('market')
            ->firstOrFail();

        $vendor = \Webkul\Marketplace\Models\Seller::findOrFail($request->vendor_id);
        
        $pricing = $this->pricing->calculateServiceCost(
            $property,
            $request->service_type,
            $request->priority,
            $vendor
        );

        $bookingData = $request->all();

        return view('property_services::shop.booking.review', compact(
            'property', 'vendor', 'pricing', 'bookingData'
        ));
    }

    /**
     * Confirm and create the service request.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'service_type' => 'required|string',
            'property_id' => 'required|exists:property_services_properties,id',
            'requested_datetime' => 'required|date|after:now',
            'priority' => 'required|in:standard,urgent,emergency',
            'vendor_id' => 'required|exists:marketplace_sellers,id',
            'special_requirements' => 'nullable|string|max:1000',
            'estimated_cost' => 'required|numeric|min:0'
        ]);

        $property = Property::where('id', $request->property_id)
            ->where('customer_id', auth('customer')->id())
            ->firstOrFail();

        // Create service request
        $serviceRequest = ServiceRequest::create([
            'property_id' => $property->id,
            'vendor_id' => $request->vendor_id,
            'market_id' => $property->market_id,
            'service_type' => $request->service_type,
            'requested_datetime' => $request->requested_datetime,
            'estimated_duration' => config("property_services.service_types.{$request->service_type}.base_duration"),
            'status' => ServiceRequest::STATUS_PENDING,
            'priority' => $request->priority,
            'special_requirements' => $request->special_requirements,
            'estimated_cost' => $request->estimated_cost,
            'currency_code' => $property->market->currency_code,
        ]);

        // Notify vendor (implement notification service)
        // $this->notificationService->notifyVendor($serviceRequest);

        session()->flash('success', 'Service request submitted successfully!');

        return redirect()->route('shop.customer.booking.success', $serviceRequest);
    }

    /**
     * Booking success page.
     */
    public function success(ServiceRequest $serviceRequest): View
    {
        // Ensure customer can only view their own requests
        if ($serviceRequest->property->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        $serviceRequest->load(['property', 'vendor', 'market']);

        return view('property_services::shop.booking.success', compact('serviceRequest'));
    }

    /**
     * Get available time slots for a property and service type.
     */
    private function getAvailableTimeSlots(Property $property, string $serviceType): array
    {
        $slots = [];
        $serviceConfig = config("property_services.service_types.{$serviceType}");
        $duration = $serviceConfig['base_duration'] ?? 120;

        for ($i = 0; $i < 7; $i++) {
            $date = now()->addDays($i);
            $daySlots = [];

            // Generate slots from 8 AM to 6 PM
            for ($hour = 8; $hour <= 18; $hour++) {
                $slotTime = $date->copy()->setTime($hour, 0);
                
                // Skip past times
                if ($slotTime->isPast()) {
                    continue;
                }

                // Check if slot is available (no conflicting bookings)
                $isAvailable = !ServiceRequest::where('property_id', $property->id)
                    ->where('requested_datetime', '>=', $slotTime)
                    ->where('requested_datetime', '<', $slotTime->copy()->addMinutes($duration))
                    ->whereIn('status', ['pending', 'accepted', 'in_progress'])
                    ->exists();

                if ($isAvailable) {
                    $daySlots[] = [
                        'time' => $slotTime,
                        'formatted' => $slotTime->format('g:i A'),
                        'available' => true
                    ];
                }
            }

            if (!empty($daySlots)) {
                $slots[$date->format('Y-m-d')] = [
                    'date' => $date,
                    'formatted_date' => $date->format('l, M j'),
                    'slots' => $daySlots
                ];
            }
        }

        return $slots;
    }

    /**
    * Get proximity pricing for AJAX requests.
    */
    public function getProximityPricing(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'property_id' => 'required|exists:property_services_properties,id',
            'vendor_id' => 'required|exists:marketplace_sellers,id',
            'service_type' => 'required|string',
            'requested_datetime' => 'required|date|after:now',
            'priority' => 'required|in:standard,urgent,emergency'
        ]);

        $property = \Webkul\PropertyServices\Models\Property::where('id', $request->property_id)
            ->where('customer_id', auth('customer')->id())
            ->firstOrFail();

        $vendor = \Webkul\Marketplace\Models\Seller::findOrFail($request->vendor_id);

        if (!$vendor->vendorLocation) {
            return response()->json([
                'error' => 'Vendor location not available'
            ], 400);
        }

        $pricingService = app(\Webkul\PropertyServices\Services\PricingService::class);
        $pricing = $pricingService->calculateServiceCost(
            $property,
            $request->service_type,
            $request->priority,
            $vendor,
            $request->requested_datetime
        );

        return response()->json([
            'success' => true,
            'pricing' => $pricing,
            'formatted_explanation' => $this->formatProximityExplanation($pricing)
        ]);
    }

    /**
     * Format proximity explanation for customer display.
     */
    protected function formatProximityExplanation(array $pricing): array
    {
        if (!$pricing['proximity_data']) {
            return [
                'has_proximity_charge' => false,
                'message' => 'Standard pricing applies'
            ];
        }

        $proximityData = $pricing['proximity_data'];
        $explanation = [];

        if ($proximityData['multiplier'] == 1.0) {
            return [
                'has_proximity_charge' => false,
                'message' => 'No distance charges - vendor is within standard service area',
                'distance' => round($proximityData['distance_km'], 1) . ' km'
            ];
        }

        $breakdown = $proximityData['breakdown'];
        
        if ($breakdown['distance']['multiplier'] > 1.0) {
            $explanation[] = [
                'type' => 'distance',
                'icon' => 'map-pin',
                'label' => 'Distance Charge',
                'description' => "Service location is {$proximityData['distance_km']} km away",
                'impact' => '+' . round(($breakdown['distance']['multiplier'] - 1) * 100, 1) . '%'
            ];
        }

        if ($breakdown['time']['factor'] > 1.0) {
            $timeReasons = array_column($breakdown['time']['adjustments'], 'description');
            $explanation[] = [
                'type' => 'time',
                'icon' => 'clock',
                'label' => 'Time Premium',
                'description' => implode(', ', $timeReasons),
                'impact' => '+' . round(($breakdown['time']['factor'] - 1) * 100, 1) . '%'
            ];
        }

        if ($breakdown['service_type']['factor'] > 1.0) {
            $explanation[] = [
                'type' => 'service',
                'icon' => 'tool',
                'label' => 'Service Type',
                'description' => ucfirst($breakdown['service_type']['service_type']) . ' services require specialized equipment',
                'impact' => '+' . round(($breakdown['service_type']['factor'] - 1) * 100, 1) . '%'
            ];
        }

        return [
            'has_proximity_charge' => true,
            'total_increase' => $proximityData['total_increase_percentage'] . '%',
            'distance' => round($proximityData['distance_km'], 1) . ' km',
            'tier' => $proximityData['tier'],
            'explanation' => $explanation,
            'summary' => $pricing['breakdown']['proximity_explanation']
        ];
    }

}