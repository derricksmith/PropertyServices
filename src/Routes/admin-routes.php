<?php

use Illuminate\Support\Facades\Route;
use Webkul\PropertyServices\Http\Controllers\Admin\PropertyController;
use Webkul\PropertyServices\Http\Controllers\Admin\ServiceRequestController;
use Webkul\PropertyServices\Http\Controllers\Admin\MarketController;
use Webkul\PropertyServices\Http\Controllers\Admin\ServiceAreaController;
use Webkul\PropertyServices\Http\Controllers\Admin\DashboardController;
use Webkul\PropertyServices\Http\Controllers\Admin\VendorController;

Route::group([
    'middleware' => ['web', 'admin'],
    'prefix' => config('app.admin_url', 'admin'),
], function () {
    Route::prefix('property-services')->name('property_services.admin.')->group(function () {
        
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
        
        // Properties Management
        Route::resource('properties', PropertyController::class);
        Route::get('properties/{property}/nearby', [PropertyController::class, 'getNearbyProperties'])
            ->name('properties.nearby');
        Route::post('properties/bulk-action', [PropertyController::class, 'bulkAction'])
            ->name('properties.bulk-action');
        
        // Service Requests Management
        Route::resource('service-requests', ServiceRequestController::class);
        Route::post('service-requests/{serviceRequest}/assign', [ServiceRequestController::class, 'assign'])
            ->name('service-requests.assign');
        Route::patch('service-requests/{serviceRequest}/status', [ServiceRequestController::class, 'updateStatus'])
            ->name('service-requests.status');
        Route::get('service-requests/{serviceRequest}/timeline', [ServiceRequestController::class, 'timeline'])
            ->name('service-requests.timeline');
        Route::post('service-requests/bulk-action', [ServiceRequestController::class, 'bulkAction'])
            ->name('service-requests.bulk-action');
        
        // Markets Management
        Route::resource('markets', MarketController::class);
        Route::patch('markets/{market}/toggle', [MarketController::class, 'toggleActive'])
            ->name('markets.toggle');
        Route::get('markets/{market}/analytics', [MarketController::class, 'analytics'])
            ->name('markets.analytics');
        
        // Service Areas Management
        Route::resource('service-areas', ServiceAreaController::class);
        Route::post('service-areas/{serviceArea}/test-polygon', [ServiceAreaController::class, 'testPolygon'])
            ->name('service-areas.test-polygon');
        
        // Vendor Management (Extended)
        Route::prefix('vendors')->name('vendors.')->group(function () {
            Route::get('/', [VendorController::class, 'index'])->name('index');
            Route::get('{seller}', [VendorController::class, 'show'])->name('show');
            Route::patch('{seller}/verify', [VendorController::class, 'verify'])->name('verify');
            Route::patch('{seller}/suspend', [VendorController::class, 'suspend'])->name('suspend');
            Route::get('{seller}/performance', [VendorController::class, 'performance'])->name('performance');
            Route::post('{seller}/update-service-areas', [VendorController::class, 'updateServiceAreas'])
                ->name('update-service-areas');
        });
        
        // Reports and Analytics
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('overview', [DashboardController::class, 'overview'])->name('overview');
            Route::get('revenue', [DashboardController::class, 'revenue'])->name('revenue');
            Route::get('vendor-performance', [DashboardController::class, 'vendorPerformance'])->name('vendor-performance');
            Route::get('market-analysis', [DashboardController::class, 'marketAnalysis'])->name('market-analysis');
        });
    });
});

