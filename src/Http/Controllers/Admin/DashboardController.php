<?php

// packages/Webkul/PropertyServices/src/Http/Controllers/Admin/DashboardController.php

namespace Webkul\PropertyServices\Http\Controllers\Admin;

use Webkul\Admin\Http\Controllers\Controller;
use Webkul\PropertyServices\Models\Property;
use Webkul\PropertyServices\Models\ServiceRequest;
use Webkul\PropertyServices\Models\Market;
use Webkul\PropertyServices\Models\VendorLocation;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_properties' => Property::count(),
            'active_properties' => Property::where('is_active', true)->count(),
            'total_service_requests' => ServiceRequest::count(),
            'pending_requests' => ServiceRequest::where('status', 'pending')->count(),
            'in_progress_requests' => ServiceRequest::where('status', 'in_progress')->count(),
            'completed_requests' => ServiceRequest::where('status', 'completed')->count(),
            'active_markets' => Market::where('is_active', true)->count(),
            'available_vendors' => VendorLocation::where('is_available', true)
                ->where('last_updated', '>=', now()->subMinutes(30))
                ->count()
        ];

        // Recent service requests
        $recentRequests = ServiceRequest::with(['property.customer', 'vendor', 'market'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Revenue stats (last 30 days)
        $monthlyRevenue = ServiceRequest::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('actual_cost');

        return view('property_services::admin.dashboard.index', compact(
            'stats', 'recentRequests', 'monthlyRevenue'
        ));
    }
}
