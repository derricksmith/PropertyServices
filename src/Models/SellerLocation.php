<?php

namespace Webkul\PropertyServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Marketplace\Models\Seller;

class SellerLocation extends Model
{
    protected $table = 'property_services_seller_locations';

    protected $fillable = [
        'seller_id',
        'latitude',
        'longitude',
        'is_available',
        'last_updated'
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_available' => 'boolean',
        'last_updated' => 'datetime'
    ];

    /**
     * Get the seller for this location.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Update seller location and availability.
     */
    public static function updateSellerLocation(int $sellerId, float $lat, float $lng, bool $available = true): self
    {
        return self::updateOrCreate(
            ['seller_id' => $sellerId],
            [
                'latitude' => $lat,
                'longitude' => $lng,
                'is_available' => $available,
                'last_updated' => now()
            ]
        );
    }

    /**
     * Find sellers within radius of a point.
     */
    public static function findSellersWithinRadius(float $lat, float $lng, float $radiusKm = 10): \Illuminate\Database\Eloquent\Collection
    {
        $earthRadius = 6371; // km

        return self::selectRaw("
                *,
                ( $earthRadius * acos( cos( radians(?) ) *
                  cos( radians( latitude ) ) *
                  cos( radians( longitude ) - radians(?) ) +
                  sin( radians(?) ) *
                  sin( radians( latitude ) ) ) ) AS distance
            ", [$lat, $lng, $lat])
            ->where('is_available', true)
            ->where('last_updated', '>=', now()->subMinutes(30)) // Only recent locations
            ->having('distance', '<', $radiusKm)
            ->orderBy('distance')
            ->get();
    }

    /**
     * Legacy method for backward compatibility.
     * @deprecated Use findSellersWithinRadius() instead
     */
    public static function findVendorsWithinRadius(float $lat, float $lng, float $radiusKm = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::findSellersWithinRadius($lat, $lng, $radiusKm);
    }
}