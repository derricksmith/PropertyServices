<?php

namespace Webkul\PropertyServices\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Webkul\PropertyServices\Models\PropertyImportConnection;
use Webkul\PropertyServices\Models\PropertyImport;
use Webkul\PropertyServices\Models\Property;
use Webkul\PropertyServices\Models\Market;
use Webkul\PropertyServices\Contracts\PropertyImportProvider;
use Webkul\PropertyServices\Services\Providers\VrboImportProvider;
use Webkul\PropertyServices\Services\Providers\OwnerReservationsImportProvider;

class PropertyImportService
{
    protected array $providers = [];

    public function __construct()
    {
        $this->registerProviders();
    }

    /**
     * Register import providers.
     */
    protected function registerProviders(): void
    {
        $this->providers = [
            'vrbo' => VrboImportProvider::class,
            'owner_reservations' => OwnerReservationsImportProvider::class,
        ];
    }

    /**
     * Test API connection for a provider.
     */
    public function testConnection(PropertyImportConnection $connection): array
    {
        try {
            $provider = $this->getProvider($connection->provider);
            $result = $provider->testConnection($connection);

            $connection->update([
                'last_sync_status' => $result['success'] ? 'success' : 'failed',
                'sync_errors' => $result['success'] ? null : [$result['message']]
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error("Property import connection test failed", [
                'connection_id' => $connection->id,
                'provider' => $connection->provider,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Import properties for a connection.
     */
    public function importProperties(PropertyImportConnection $connection): array
    {
        try {
            $provider = $this->getProvider($connection->provider);
            
            // Fetch properties from external API
            $externalProperties = $provider->fetchProperties($connection);
            
            $results = [
                'total' => count($externalProperties),
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'errors' => []
            ];

            foreach ($externalProperties as $externalProperty) {
                $result = $this->processPropertyImport($connection, $externalProperty);
                $results[$result['status']]++;
                
                if (isset($result['error'])) {
                    $results['errors'][] = $result['error'];
                }
            }

            // Update connection sync status
            $status = $results['failed'] > 0 ? 
                ($results['imported'] + $results['updated'] > 0 ? 'partial' : 'failed') : 
                'success';

            $connection->update([
                'last_sync_at' => now(),
                'last_sync_status' => $status,
                'sync_errors' => empty($results['errors']) ? null : $results['errors']
            ]);

            return $results;

        } catch (\Exception $e) {
            Log::error("Property import failed", [
                'connection_id' => $connection->id,
                'provider' => $connection->provider,
                'error' => $e->getMessage()
            ]);

            $connection->update([
                'last_sync_at' => now(),
                'last_sync_status' => 'failed',
                'sync_errors' => [$e->getMessage()]
            ]);

            return [
                'total' => 0,
                'imported' => 0,
                'updated' => 0,
                'skipped' => 0,
                'failed' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Process individual property import with duplicate detection.
     */
    protected function processPropertyImport(PropertyImportConnection $connection, array $externalProperty): array
    {
        try {
            // Generate duplicate detection hash
            $duplicateHash = PropertyImport::generateDuplicateHash($externalProperty);
            
            // Check for existing import record
            $existingImport = PropertyImport::findDuplicate($duplicateHash, $connection->customer_id);
            
            if ($existingImport) {
                // Update existing property if data has changed
                if ($this->hasPropertyDataChanged($existingImport, $externalProperty)) {
                    $this->updateExistingProperty($existingImport, $externalProperty);
                    return ['status' => 'updated'];
                } else {
                    return ['status' => 'skipped'];
                }
            }

            // Create new property
            $property = $this->createNewProperty($connection, $externalProperty);
            
            // Create import record
            PropertyImport::create([
                'customer_id' => $connection->customer_id,
                'connection_id' => $connection->id,
                'property_id' => $property->id,
                'external_id' => $externalProperty['external_id'],
                'external_data' => $externalProperty,
                'import_status' => 'imported',
                'last_imported_at' => now(),
                'duplicate_detection_hash' => $duplicateHash
            ]);

            return ['status' => 'imported'];

        } catch (\Exception $e) {
            Log::error("Individual property import failed", [
                'connection_id' => $connection->id,
                'external_id' => $externalProperty['external_id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if property data has changed.
     */
    protected function hasPropertyDataChanged(PropertyImport $import, array $newData): bool
    {
        $oldData = $import->external_data;
        
        // Compare key fields that would trigger an update
        $compareFields = ['name', 'address', 'bedrooms', 'bathrooms', 'square_footage', 'description'];
        
        foreach ($compareFields as $field) {
            if (($oldData[$field] ?? null) !== ($newData[$field] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Update existing property with new data.
     */
    protected function updateExistingProperty(PropertyImport $import, array $newData): void
    {
        $property = $import->property;
        
        if ($property) {
            $property->update([
                'name' => $newData['name'],
                'address' => $newData['address'],
                'latitude' => $newData['latitude'],
                'longitude' => $newData['longitude'],
                'bedrooms' => $newData['bedrooms'],
                'bathrooms' => $newData['bathrooms'],
                'square_footage' => $newData['square_footage'],
                'special_instructions' => $newData['description']
            ]);
        }

        $import->update([
            'external_data' => $newData,
            'last_imported_at' => now(),
            'import_status' => 'updated'
        ]);
    }

    /**
     * Create new property from imported data.
     */
    protected function createNewProperty(PropertyImportConnection $connection, array $data): Property
    {
        // Find appropriate market based on location
        $market = $this->findMarketForProperty($data);
        
        if (!$market) {
            throw new \Exception("No active market found for property location");
        }

        return Property::create([
            'customer_id' => $connection->customer_id,
            'market_id' => $market->id,
            'name' => $data['name'],
            'address' => $data['address'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'property_type' => 'vacation', // Default for VRBO/OR imports
            'square_footage' => $data['square_footage'],
            'bedrooms' => $data['bedrooms'],
            'bathrooms' => $data['bathrooms'],
            'special_instructions' => $data['description'] . "\n\nImported from " . $connection->provider_name,
            'is_active' => true
        ]);
    }

    /**
     * Find appropriate market for property based on location.
     */
    protected function findMarketForProperty(array $propertyData): ?Market
    {
        // Try to find market by coordinates first (most accurate)
        if (!empty($propertyData['latitude']) && !empty($propertyData['longitude'])) {
            $markets = Market::where('is_active', true)->get();
            
            foreach ($markets as $market) {
                // Simple distance check - in production, you'd use more sophisticated geofencing
                $distance = $this->calculateDistance(
                    $propertyData['latitude'],
                    $propertyData['longitude'],
                    $market->latitude ?? 0, // You'd need to add lat/lng to markets table
                    $market->longitude ?? 0
                );
                
                if ($distance < 50) { // Within 50km of market center
                    return $market;
                }
            }
        }

        // Fallback to address-based matching
        $address = strtolower($propertyData['address'] ?? '');
        
        // Simple city matching - enhance this based on your market setup
        $cityMatches = [
            'austin' => 'Austin',
            'nashville' => 'Nashville',
            'denver' => 'Denver'
        ];

        foreach ($cityMatches as $keyword => $cityName) {
            if (str_contains($address, $keyword)) {
                return Market::where('city_name', $cityName)
                    ->where('is_active', true)
                    ->first();
            }
        }

        // Return default market if no specific match
        return Market::where('is_active', true)->first();
    }

    /**
     * Calculate distance between two points.
     */
    protected function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get provider instance.
     */
    protected function getProvider(string $providerName): PropertyImportProvider
    {
        if (!isset($this->providers[$providerName])) {
            throw new \Exception("Unknown import provider: {$providerName}");
        }

        return app($this->providers[$providerName]);
    }

    /**
     * Validate import connection credentials.
     */
    public function validateCredentials(string $provider, array $credentials): array
    {
        try {
            $providerInstance = $this->getProvider($provider);
            $requiredFields = $providerInstance->getRequiredCredentials();
            
            $errors = [];
            
            foreach ($requiredFields as $field => $config) {
                if ($config['required'] && empty($credentials[$field])) {
                    $errors[$field] = "The {$config['label']} field is required.";
                }
            }
            
            if (!empty($errors)) {
                return ['valid' => false, 'errors' => $errors];
            }
            
            // Create temporary connection for testing
            $tempConnection = new \Webkul\PropertyServices\Models\PropertyImportConnection([
                'provider' => $provider,
                'api_credentials' => $credentials
            ]);
            
            $testResult = $providerInstance->testConnection($tempConnection);
            
            return [
                'valid' => $testResult['success'],
                'message' => $testResult['message'],
                'errors' => $testResult['success'] ? [] : ['api' => $testResult['message']]
            ];
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'errors' => ['general' => $e->getMessage()]
            ];
        }
    }

    /**
     * Import properties for all active connections.
     */
    public function importAllActiveConnections(): array
    {
        $connections = PropertyImportConnection::where('is_active', true)
            ->where('auto_sync_enabled', true)
            ->get();

        $results = [];

        foreach ($connections as $connection) {
            $nextSync = $connection->getNextSyncTime();
            
            if ($nextSync && $nextSync <= now()) {
                $results[$connection->id] = $this->importProperties($connection);
            }
        }

        return $results;
    }
}