<?php

use Illuminate\Support\Facades\Route;
use Webkul\PropertyServices\Http\Controllers\Shop\PropertyController;
use Webkul\PropertyServices\Http\Controllers\Shop\ServiceController;
use Webkul\PropertyServices\Http\Controllers\Shop\BookingController;

Route::group([
    'middleware' => ['web', 'theme', 'locale', 'currency'],
], function () {

    // Public property services (browsing without login)
    Route::prefix('property-services')->name('property_services.shop.')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::get('services', [ServiceController::class, 'services'])->name('services');
        Route::get('how-it-works', [ServiceController::class, 'howItWorks'])->name('how-it-works');
        Route::get('markets', [ServiceController::class, 'markets'])->name('markets');
    });

    // PropertyServices Homepage
    Route::get('/property-services', [PropertyServicesController::class, 'index'])->name('property_services.shop.index');
    Route::post('/property-services/api/nearby-sellers', [PropertyServicesController::class, 'getNearbySeliers'])->name('property_services.shop.api.nearby-sellers');
    Route::get('/property-services/api/service-types', [PropertyServicesController::class, 'getServiceTypes'])->name('property_services.shop.api.service-types');
    Route::get('/property-services/api/property-details', [PropertyServicesController::class, 'getPropertyDetails'])->name('property_services.shop.api.property-details');
    Route::post('/property-services/start-booking', [PropertyServicesController::class, 'startBooking'])->name('property_services.shop.start-booking');


    // Customer authenticated routes
    Route::group(['middleware' => 'customer'], function () {
        
        // Customer Property Management
        Route::prefix('customer/properties')->name('shop.customer.properties.')->group(function () {
            Route::get('/', [PropertyController::class, 'index'])->name('index');
            Route::get('create', [PropertyController::class, 'create'])->name('create');
            Route::post('/', [PropertyController::class, 'store'])->name('store');
            Route::get('{property}', [PropertyController::class, 'show'])->name('show');
            Route::get('{property}/edit', [PropertyController::class, 'edit'])->name('edit');
            Route::put('{property}', [PropertyController::class, 'update'])->name('update');
            Route::delete('{property}', [PropertyController::class, 'destroy'])->name('destroy');
            
            // Property-specific service requests
            Route::prefix('{property}/services')->name('services.')->group(function () {
                Route::get('/', [ServiceController::class, 'propertyServices'])->name('index');
                Route::get('request', [BookingController::class, 'create'])->name('create');
                Route::post('request', [BookingController::class, 'store'])->name('store');
                Route::get('history', [ServiceController::class, 'propertyHistory'])->name('history');
            });
        });
        
        // Customer Service Management
        Route::prefix('customer/services')->name('shop.customer.services.')->group(function () {
            Route::get('/', [ServiceController::class, 'customerServices'])->name('index');
            Route::get('history', [ServiceController::class, 'history'])->name('history');
            Route::get('{serviceRequest}', [ServiceController::class, 'show'])->name('show');
            Route::post('{serviceRequest}/review', [ServiceController::class, 'review'])->name('review');
            Route::post('{serviceRequest}/cancel', [ServiceController::class, 'cancel'])->name('cancel');
            Route::get('{serviceRequest}/track', [ServiceController::class, 'track'])->name('track');
        });

        // Booking Flow
        Route::prefix('customer/booking')->name('shop.customer.booking.')->group(function () {
            Route::get('service-type', [BookingController::class, 'selectServiceType'])->name('service-type');
            Route::get('property', [BookingController::class, 'selectProperty'])->name('property');
            Route::get('datetime', [BookingController::class, 'selectDateTime'])->name('datetime');
            Route::get('vendors', [BookingController::class, 'selectVendor'])->name('vendors');
            Route::get('review', [BookingController::class, 'review'])->name('review');
            Route::post('confirm', [BookingController::class, 'confirm'])->name('confirm');
            Route::get('success/{serviceRequest}', [BookingController::class, 'success'])->name('success');
        });

        Route::prefix('customer/property-imports')->name('shop.customer.imports.')->group(function () {
            Route::get('/', [PropertyImportController::class, 'index'])->name('index');
            Route::get('create', [PropertyImportController::class, 'create'])->name('create');
            Route::post('/', [PropertyImportController::class, 'store'])->name('store');
            Route::get('{connection}', [PropertyImportController::class, 'show'])->name('show');
            Route::get('{connection}/edit', [PropertyImportController::class, 'edit'])->name('edit');
            Route::put('{connection}', [PropertyImportController::class, 'update'])->name('update');
            Route::delete('{connection}', [PropertyImportController::class, 'destroy'])->name('destroy');
            
            Route::post('{connection}/test', [PropertyImportController::class, 'testConnection'])->name('test');
            Route::post('{connection}/import', [PropertyImportController::class, 'manualImport'])->name('manual-import');
            Route::patch('{connection}/toggle', [PropertyImportController::class, 'toggleActive'])->name('toggle');
            
            Route::get('history/all', [PropertyImportController::class, 'history'])->name('history');
            Route::delete('imported/{import}', [PropertyImportController::class, 'removeImportedProperty'])->name('remove-imported');
        });
    });
});