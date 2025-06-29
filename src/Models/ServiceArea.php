<?php

namespace Webkul\PropertyServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceArea extends Model
{
    protected $table = 'property_services_service_areas';

    protected $fillable = [
        'market_id',
        'area_name',
        'boundary_polygon',
        'is_active'
    ];

    protected $casts = [
        'boundary_polygon' => 'json',
        'is_active' => 'boolean'
    ];

    /**
     * Get the market for this service area.
     */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /**
     * Check if a point is within this service area.
     */
    public function containsPoint(float $latitude, float $longitude): bool
    {
        if (!$this->boundary_polygon) {
            return false;
        }

        $polygon = $this->boundary_polygon['coordinates'][0] ?? [];
        
        return $this->pointInPolygon($latitude, $longitude, $polygon);
    }

    /**
     * Point in polygon algorithm.
     */
    private function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $j = count($polygon) - 1;

        for ($i = 0; $i < count($polygon); $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];

            if ((($yi > $lat) != ($yj > $lat)) &&
                ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
            $j = $i;
        }

        return $inside;
    }
}
