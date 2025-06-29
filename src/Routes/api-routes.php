<?php

use Illuminate\Support\Facades\Route;
use Webkul\PropertyServices\Http\Controllers\Api\LocationController;
use Webkul\PropertyServices\Http\Controllers\Api\VendorController;
use Webkul\PropertyServices\Http\Controllers\Api\ServiceController;

Route::group([
    'middleware' => ['api'],
    'prefix' => 'api/v1/property-services',
    'name' => 'api.property_services.'
], function () {
    
    // Location-based services
    Route::prefix('location')->name('location.')->group(function () {
        Route::post('vendors/update', [LocationController::class, 'updateVendorLocation'])->name('vendors.update');
        Route::get('vendors/nearby', [LocationController::class, 'getNearbyVendors'])->name('vendors.nearby');
        Route::get('properties/nearby', [LocationController::class, 'getNearbyProperties'])->name('properties.nearby');
        Route::post('validate-address', [LocationController::class, 'validateAddress'])->name('validate-address');
        Route::post('geocode', [LocationController::class, 'geocodeAddress'])->name('geocode');
    });
    
    // Service matching and booking
    Route::prefix('services')->name('services.')->group(function () {
        Route::post('match-vendors', [ServiceController::class, 'matchVendors'])->name('match-vendors');
        Route::post('estimate-cost', [ServiceController::class, 'estimateCost'])->name('estimate-cost');
        Route::post('check-availability', [ServiceController::class, 'checkAvailability'])->name('check-availability');
        Route::get('types', [ServiceController::class, 'getServiceTypes'])->name('types');
    });
    
    // Vendor API endpoints
    Route::prefix('vendor')->middleware('marketplace.vendor')->name('vendor.')->group(function () {
        Route::get('dashboard', [VendorController::class, 'dashboard'])->name('dashboard');
        Route::get('requests', [VendorController::class, 'getRequests'])->name('requests');
        Route::post('requests/{serviceRequest}/accept', [VendorController::class, 'acceptRequest'])->name('requests.accept');
        Route::post('requests/{serviceRequest}/decline', [VendorController::class, 'declineRequest'])->name('requests.decline');
        Route::patch('requests/{serviceRequest}/status', [VendorController::class, 'updateStatus'])->name('requests.status');
        Route::post('availability', [VendorController::class, 'updateAvailability'])->name('availability');
        Route::get('earnings', [VendorController::class, 'earnings'])->name('earnings');
        Route::get('profile', [VendorController::class, 'profile'])->name('profile');
        Route::patch('profile', [VendorController::class, 'updateProfile'])->name('profile.update');
    });
    
    // Real-time updates (WebSocket endpoints)
    Route::prefix('realtime')->name('realtime.')->group(function () {
        Route::post('service-status', [ServiceController::class, 'updateServiceStatus'])->name('service-status');
        Route::post('vendor-location', [LocationController::class, 'broadcastVendorLocation'])->name('vendor-location');
        Route::get('service/{serviceRequest}/updates', [ServiceController::class, 'getServiceUpdates'])->name('service.updates');
    });
});