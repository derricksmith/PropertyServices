<?php

namespace Webkul\PropertyServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Customer\Models\Customer;

class Property extends Model
{
    protected $table = 'property_services_properties';

    protected $fillable = [
        'customer_id',
        'market_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'property_type',
        'square_footage',
        'bedrooms',
        'bathrooms',
        'special_instructions',
        'access_codes',
        'is_active'
    ];

    protected $casts = [
        'access_codes' => 'json',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean'
    ];

    /**
     * Get the customer that owns the property.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the market for the property.
     */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /**
     * Get service requests for this property.
     */
    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    /**
     * Calculate distance to a given point.
     */
    public function distanceTo(float $latitude, float $longitude): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($latitude - $this->latitude);
        $lonDelta = deg2rad($longitude - $this->longitude);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($latitude)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}