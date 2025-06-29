<?php

// packages/Webkul/PropertyServices/src/Http/Controllers/Admin/ServiceRequestController.php

namespace Webkul\PropertyServices\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\PropertyServices\Models\ServiceRequest;
use Webkul\PropertyServices\Models\Property;
use Webkul\Marketplace\Models\Seller;

class ServiceRequestController extends Controller
{
    public function index()
    {
        $serviceRequests = ServiceRequest::with(['property.customer', 'vendor', 'market'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('property_services::admin.service-requests.index', compact('serviceRequests'));
    }

    public function show(ServiceRequest $serviceRequest)
    {
        $serviceRequest->load(['property.customer', 'vendor', 'market']);
        
        return view('property_services::admin.service-requests.show', compact('serviceRequest'));
    }

    public function assign(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'vendor_id' => 'required|exists:marketplace_sellers,id'
        ]);

        $serviceRequest->update([
            'vendor_id' => $request->vendor_id,
            'status' => ServiceRequest::STATUS_ACCEPTED
        ]);

        session()->flash('success', 'Service request assigned successfully.');

        return redirect()->back();
    }

    public function updateStatus(Request $request, ServiceRequest $serviceRequest)
    {
        $request->validate([
            'status' => 'required|in:pending,accepted,in_progress,completed,cancelled',
            'actual_cost' => 'nullable|numeric|min:0'
        ]);

        $updateData = ['status' => $request->status];
        
        if ($request->has('actual_cost')) {
            $updateData['actual_cost'] = $request->actual_cost;
        }

        $serviceRequest->update($updateData);

        session()->flash('success', 'Service request status updated.');

        return redirect()->back();
    }
}