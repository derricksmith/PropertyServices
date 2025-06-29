<?php

namespace Webkul\PropertyServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Customer\Models\Customer;

class PropertyImport extends Model
{
    protected $table = 'property_services_property_imports';

    protected $fillable = [
        'customer_id',
        'connection_id',
        'property_id',
        'external_id',
        'external_data',
        'import_status',
        'last_imported_at',
        'import_errors',
        'duplicate_detection_hash'
    ];

    protected $casts = [
        'external_data' => 'json',
        'last_imported_at' => 'datetime',
        'import_errors' => 'json'
    ];

    const IMPORT_STATUSES = [
        'imported' => 'Successfully Imported',
        'updated' => 'Updated Existing',
        'skipped' => 'Skipped (Duplicate)',
        'failed' => 'Import Failed',
        'pending' => 'Pending Import'
    ];

    /**
     * Get the customer that owns this import.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the import connection.
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(PropertyImportConnection::class, 'connection_id');
    }

    /**
     * Get the imported property.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * Generate duplicate detection hash.
     */
    public static function generateDuplicateHash(array $data): string
    {
        // Create hash based on address, name, and key identifiers
        $hashData = [
            'address' => strtolower(trim($data['address'] ?? '')),
            'name' => strtolower(trim($data['name'] ?? '')),
            'coordinates' => ($data['latitude'] ?? '') . ',' . ($data['longitude'] ?? ''),
            'bedrooms' => $data['bedrooms'] ?? 0,
            'bathrooms' => $data['bathrooms'] ?? 0
        ];

        return hash('sha256', json_encode($hashData));
    }

    /**
     * Check for existing duplicate.
     */
    public static function findDuplicate(string $hash, int $customerId): ?self
    {
        return self::where('duplicate_detection_hash', $hash)
            ->where('customer_id', $customerId)
            ->first();
    }
}
