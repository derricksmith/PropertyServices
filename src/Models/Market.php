<?php

namespace Webkul\PropertyServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Market extends Model
{
    protected $table = 'property_services_markets';

    protected $fillable = [
        'country_code',
        'region_code',
        'city_name',
        'currency_code',
        'timezone',
        'is_active',
        'launch_date',
        'commission_rate',
        'tax_rate',
        'min_service_fee',
        'emergency_multiplier'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'launch_date' => 'date',
        'commission_rate' => 'decimal:4',
        'tax_rate' => 'decimal:4',
        'min_service_fee' => 'decimal:8',
        'emergency_multiplier' => 'decimal:3'
    ];

    /**
     * Get properties in this market.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    /**
     * Get service areas in this market.
     */
    public function serviceAreas(): HasMany
    {
        return $this->hasMany(ServiceArea::class);
    }

    /**
     * Get service requests in this market.
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * Check if market is currently active.
     */
    public function isActiveMarket(): bool
    {
        return $this->is_active && 
               $this->launch_date <= now()->format('Y-m-d');
    }
}
