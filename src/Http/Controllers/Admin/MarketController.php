<?php

// packages/Webkul/PropertyServices/src/Http/Controllers/Admin/MarketController.php

namespace Webkul\PropertyServices\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\PropertyServices\Models\Market;

class MarketController extends Controller
{
    public function index()
    {
        $markets = Market::withCount(['properties', 'serviceRequests'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('property_services::admin.markets.index', compact('markets'));
    }

    public function create()
    {
        return view('property_services::admin.markets.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'country_code' => 'required|string|size:2',
            'region_code' => 'nullable|string|max:10',
            'city_name' => 'required|string|max:100',
            'currency_code' => 'required|string|size:3',
            'timezone' => 'required|string|max:50',
            'launch_date' => 'required|date',
            'commission_rate' => 'required|numeric|between:0,100',
            'tax_rate' => 'required|numeric|between:0,100',
            'min_service_fee' => 'required|numeric|min:0',
            'emergency_multiplier' => 'required|numeric|min:1'
        ]);

        Market::create($request->all());

        session()->flash('success', 'Market created successfully.');

        return redirect()->route('property_services.admin.markets.index');
    }

    public function show(Market $market)
    {
        $market->loadCount(['properties', 'serviceRequests', 'serviceAreas']);
        
        return view('property_services::admin.markets.show', compact('market'));
    }

    public function edit(Market $market)
    {
        return view('property_services::admin.markets.edit', compact('market'));
    }

    public function update(Request $request, Market $market)
    {
        $request->validate([
            'city_name' => 'required|string|max:100',
            'currency_code' => 'required|string|size:3',
            'timezone' => 'required|string|max:50',
            'launch_date' => 'required|date',
            'commission_rate' => 'required|numeric|between:0,100',
            'tax_rate' => 'required|numeric|between:0,100',
            'min_service_fee' => 'required|numeric|min:0',
            'emergency_multiplier' => 'required|numeric|min:1'
        ]);

        $market->update($request->all());

        session()->flash('success', 'Market updated successfully.');

        return redirect()->route('property_services.admin.markets.index');
    }

    public function destroy(Market $market)
    {
        if ($market->properties()->exists()) {
            session()->flash('error', 'Cannot delete market with existing properties.');
            return redirect()->back();
        }

        $market->delete();

        session()->flash('success', 'Market deleted successfully.');

        return redirect()->route('property_services.admin.markets.index');
    }

    public function toggleActive(Market $market)
    {
        $market->update(['is_active' => !$market->is_active]);

        $status = $market->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Market {$status} successfully.");

        return redirect()->back();
    }
}
