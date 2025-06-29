<?php
// packages/Webkul/PropertyServices/src/Models/PropertyImportConnection.php

namespace Webkul\PropertyServices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Customer\Models\Customer;
use Illuminate\Database\Eloquent\Casts\Encrypted;

class PropertyImportConnection extends Model
{
    protected $table = 'property_services_import_connections';

    protected $fillable = [
        'customer_id',
        'provider',
        'api_credentials',
        'is_active',
        'auto_sync_enabled',
        'sync_frequency',
        'last_sync_at',
        'last_sync_status',
        'sync_errors',
        'settings'
    ];

    protected $casts = [
        'api_credentials' => Encrypted::class,
        'is_active' => 'boolean',
        'auto_sync_enabled' => 'boolean',
        'last_sync_at' => 'datetime',
        'sync_errors' => 'json',
        'settings' => 'json'
    ];

    const PROVIDERS = [
        'vrbo' => 'VRBO',
        'owner_reservations' => 'Owner Reservations',
        'airbnb' => 'Airbnb' // Future expansion
    ];

    const SYNC_FREQUENCIES = [
        'hourly' => 'Every Hour',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'manual' => 'Manual Only'
    ];

    const SYNC_STATUSES = [
        'success' => 'Success',
        'partial' => 'Partial Success',
        'failed' => 'Failed',
        'pending' => 'Pending'
    ];

    /**
     * Get the customer that owns this connection.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get imported properties for this connection.
     */
    public function importedProperties(): HasMany
    {
        return $this->hasMany(PropertyImport::class, 'connection_id');
    }

    /**
     * Check if connection is ready for sync.
     */
    public function isReadyForSync(): bool
    {
        return $this->is_active && 
               $this->auto_sync_enabled && 
               !empty($this->api_credentials);
    }

    /**
     * Get next scheduled sync time.
     */
    public function getNextSyncTime(): ?\Carbon\Carbon
    {
        if (!$this->isReadyForSync() || $this->sync_frequency === 'manual') {
            return null;
        }

        $lastSync = $this->last_sync_at ?? $this->created_at;

        return match($this->sync_frequency) {
            'hourly' => $lastSync->addHour(),
            'daily' => $lastSync->addDay(),
            'weekly' => $lastSync->addWeek(),
            default => null
        };
    }

    /**
     * Test API connection.
     */
    public function testConnection(): array
    {
        $service = app(\Webkul\PropertyServices\Services\PropertyImportService::class);
        return $service->testConnection($this);
    }

    /**
     * Get provider display name.
     */
    public function getProviderNameAttribute(): string
    {
        return self::PROVIDERS[$this->provider] ?? $this->provider;
    }
}

// packages/Webkul/PropertyServices/src/Models/PropertyImport.php

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
