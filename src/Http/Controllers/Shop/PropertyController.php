<?php

namespace Webkul\PropertyServices\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\PropertyServices\Models\Property;
use Webkul\PropertyServices\Models\Market;

class PropertyController extends Controller
{
    /**
     * Display a listing of properties.
     */
    public function index()
    {
        $properties = Property::with(['customer', 'market'])
            ->paginate(15);

        return view('property_services::admin.properties.index', compact('properties'));
    }

    /**
     * Show the form for creating a new property.
     */
    public function create()
    {
        $markets = Market::where('is_active', true)->get();
        
        return view('property_services::admin.properties.create', compact('markets'));
    }

    /**
     * Store a newly created property.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'market_id' => 'required|exists:property_services_markets,id',
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'property_type' => 'required|in:rental,vacation,commercial',
            'square_footage' => 'nullable|integer|min:1',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
        ]);

        Property::create($request->all());

        session()->flash('success', trans('property_services::app.admin.properties.create-success'));

        return redirect()->route('property_services.admin.properties.index');
    }

    /**
     * Display the specified property.
     */
    public function show(Property $property)
    {
        $property->load(['customer', 'market', 'serviceRequests.vendor']);
        
        return view('property_services::admin.properties.show', compact('property'));
    }

    /**
     * Show the form for editing the property.
     */
    public function edit(Property $property)
    {
        $markets = Market::where('is_active', true)->get();
        
        return view('property_services::admin.properties.edit', compact('property', 'markets'));
    }

    /**
     * Update the specified property.
     */
    public function update(Request $request, Property $property)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'property_type' => 'required|in:rental,vacation,commercial',
            'square_footage' => 'nullable|integer|min:1',
            'bedrooms' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
        ]);

        $property->update($request->all());

        session()->flash('success', trans('property_services::app.admin.properties.update-success'));

        return redirect()->route('property_services.admin.properties.index');
    }

    /**
     * Remove the specified property.
     */
    public function destroy(Property $property)
    {
        // Check if property has active service requests
        if ($property->serviceRequests()->whereIn('status', ['pending', 'accepted', 'in_progress'])->exists()) {
            session()->flash('error', trans('property_services::app.admin.properties.delete-error-active-requests'));
            return redirect()->back();
        }

        $property->delete();

        session()->flash('success', trans('property_services::app.admin.properties.delete-success'));

        return redirect()->route('property_services.admin.properties.index');
    }

    /**
     * Get properties near a location (API endpoint).
     */
    public function getNearbyProperties(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:50'
        ]);

        $lat = $request->latitude;
        $lng = $request->longitude;
        $radius = $request->radius ?? 10; // Default 10km

        $earthRadius = 6371; // km

        $properties = Property::selectRaw("
                *,
                ( $earthRadius * acos( cos( radians(?) ) *
                  cos( radians( latitude ) ) *
                  cos( radians( longitude ) - radians(?) ) +
                  sin( radians(?) ) *
                  sin( radians( latitude ) ) ) ) AS distance
            ", [$lat, $lng, $lat])
            ->where('is_active', true)
            ->having('distance', '<', $radius)
            ->orderBy('distance')
            ->with(['customer', 'market'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $properties
        ]);
    }
}
