<?php

namespace Webkul\PropertyServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Marketplace\Models\Seller;

class ServiceRequest extends Model
{
    protected $table = 'property_services_service_requests';

    protected $fillable = [
        'property_id',
        'seller_id', // Changed from vendor_id to seller_id
        'market_id',
        'service_type',
        'requested_datetime',
        'estimated_duration',
        'status',
        'priority',
        'is_recurring',
        'recurring_schedule',
        'parent_request_id',
        'special_requirements',
        'estimated_cost',
        'actual_cost',
        'currency_code'
    ];

    protected $casts = [
        'requested_datetime' => 'datetime',
        'is_recurring' => 'boolean',
        'recurring_schedule' => 'json',
        'estimated_cost' => 'decimal:8',
        'actual_cost' => 'decimal:8'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const PRIORITY_STANDARD = 'standard';
    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_EMERGENCY = 'emergency';

    /**
     * Get the property for this service request.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Get the seller/service provider.
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    /**
     * Legacy accessor for backward compatibility.
     * @deprecated Use seller() instead
     */
    public function vendor(): BelongsTo
    {
        return $this->seller();
    }

    /**
     * Get the market for this request.
     */
    public function market(): BelongsTo
    {
        return $this->belongsTo(Market::class);
    }

    /**
     * Get the parent request (for recurring services).
     */
    public function parentRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'parent_request_id');
    }

    /**
     * Calculate final cost including taxes and fees.
     */
    public function getFinalCost(): float
    {
        $cost = $this->actual_cost ?? $this->estimated_cost;
        $market = $this->market;

        if (!$market) {
            return $cost;
        }

        // Apply emergency multiplier
        if ($this->priority === self::PRIORITY_EMERGENCY) {
            $cost *= $market->emergency_multiplier;
        }

        // Apply minimum service fee
        $cost = max($cost, $market->min_service_fee);

        // Add taxes
        $cost += ($cost * $market->tax_rate / 100);

        return round($cost, 2);
    }
}