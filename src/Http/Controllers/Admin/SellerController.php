<?php

namespace Webkul\PropertyServices\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Marketplace\Models\Seller;
use Webkul\PropertyServices\Models\ServiceRequest;
use Webkul\PropertyServices\Models\SellerLocation;

class SellerController extends Controller
{
    /**
     * Display listing of sellers with property service capabilities.
     */
    public function index(Request $request)
    {
        $sellers = Seller::whereNotNull('service_categories')
            ->with(['sellerLocation'])
            ->withCount([
                'serviceRequests as active_requests' => function ($query) {
                    $query->whereIn('status', ['accepted', 'in_progress']);
                },
                'serviceRequests as completed_requests' => function ($query) {
                    $query->where('status', 'completed');
                }
            ])
            ->when($request->search, function ($query, $search) {
                $query->where('shop_title', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                if ($status === 'active') {
                    $query->where('is_approved', 1);
                } elseif ($status === 'inactive') {
                    $query->where('is_approved', 0);
                }
            })
            ->paginate(15);

        return view('property_services::admin.sellers.index', compact('sellers'));
    }

    /**
     * Show seller details and performance metrics.
     */
    public function show(Seller $seller)
    {
        $seller->load(['sellerLocation', 'customer']);
        
        $stats = [
            'total_requests' => $seller->serviceRequests()->count(),
            'completed_requests' => $seller->serviceRequests()->where('status', 'completed')->count(),
            'average_rating' => $seller->rating_average,
            'total_earnings' => $seller->serviceRequests()
                ->where('status', 'completed')
                ->sum('actual_cost'),
            'response_time' => $this->calculateAverageResponseTime($seller),
            'completion_rate' => $this->calculateCompletionRate($seller),
        ];

        $recentRequests = $seller->serviceRequests()
            ->with(['property.customer', 'market'])
            ->latest()
            ->limit(10)
            ->get();

        return view('property_services::admin.sellers.show', compact('seller', 'stats', 'recentRequests'));
    }

    /**
     * Verify vendor for property services.
     */
    public function verify(Seller $seller)
    {
        $seller->update([
            'is_approved' => true,
            'approved_at' => now()
        ]);

        session()->flash('success', 'Vendor verified successfully.');
        
        return redirect()->back();
    }

    /**
     * Suspend vendor from property services.
     */
    public function suspend(Seller $seller)
    {
        $seller->update(['is_approved' => false]);

        // Cancel active service requests
        $seller->serviceRequests()
            ->whereIn('status', ['pending', 'accepted'])
            ->update(['status' => 'cancelled']);

        session()->flash('success', 'Vendor suspended successfully.');
        
        return redirect()->back();
    }

    /**
     * Show vendor performance analytics.
     */
    public function performance(Seller $seller)
    {
        // Implementation for detailed performance analytics
        return view('property_services::admin.vendors.performance', compact('seller'));
    }

    /**
     * Update vendor service areas.
     */
    public function updateServiceAreas(Request $request, Seller $seller)
    {
        $request->validate([
            'service_radius' => 'required|numeric|min:1|max:50',
            'service_categories' => 'required|array',
            'service_categories.*' => 'string'
        ]);

        $seller->update([
            'service_radius' => $request->service_radius,
            'service_categories' => $request->service_categories
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Calculate average response time for vendor.
     */
    private function calculateAverageResponseTime(Seller $seller): float
    {
        $requests = $seller->serviceRequests()
            ->whereNotNull('vendor_id')
            ->whereNotNull('updated_at')
            ->get();

        if ($requests->isEmpty()) {
            return 0;
        }

        $totalMinutes = $requests->sum(function ($request) {
            return $request->created_at->diffInMinutes($request->updated_at);
        });

        return round($totalMinutes / $requests->count(), 1);
    }

    /**
     * Calculate completion rate for vendor.
     */
    private function calculateCompletionRate(Seller $seller): float
    {
        $totalRequests = $seller->serviceRequests()->whereNotNull('vendor_id')->count();
        
        if ($totalRequests === 0) {
            return 0;
        }

        $completedRequests = $seller->serviceRequests()
            ->where('status', 'completed')
            ->count();

        return round(($completedRequests / $totalRequests) * 100, 1);
    }
}