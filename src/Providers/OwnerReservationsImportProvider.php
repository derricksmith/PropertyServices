<?php

namespace Webkul\PropertyServices\Services\Providers;

use Illuminate\Support\Facades\Http;
use Webkul\PropertyServices\Models\PropertyImportConnection;
use Webkul\PropertyServices\Contracts\PropertyImportProvider;

class OwnerReservationsImportProvider implements PropertyImportProvider
{
    protected string $baseUrl = 'https://api.ownerreservations.com';

    /**
     * Test the API connection.
     */
    public function testConnection(PropertyImportConnection $connection): array
    {
        try {
            $credentials = $connection->api_credentials;
            
            $response = Http::withHeaders([
                'X-API-Key' => $credentials['api_key'],
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/v1/properties', [
                'limit' => 1
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connection successful'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'API returned error: ' . $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Fetch properties from Owner Reservations API.
     */
    public function fetchProperties(PropertyImportConnection $connection): array
    {
        $credentials = $connection->api_credentials;
        $properties = [];
        $offset = 0;
        $limit = 100;

        do {
            $response = Http::withHeaders([
                'X-API-Key' => $credentials['api_key'],
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/v1/properties', [
                'limit' => $limit,
                'offset' => $offset
            ]);

            if (!$response->successful()) {
                throw new \Exception('Owner Reservations API error: ' . $response->status());
            }

            $data = $response->json();
            $pageProperties = $data['properties'] ?? [];

            foreach ($pageProperties as $property) {
                $properties[] = $this->transformPropertyData($property);
            }

            $hasMorePages = count($pageProperties) === $limit;
            $offset += $limit;

        } while ($hasMorePages);

        return $properties;
    }

    /**
     * Transform Owner Reservations property data to our format.
     */
    protected function transformPropertyData(array $orProperty): array
    {
        return [
            'external_id' => $orProperty['id'],
            'name' => $orProperty['name'] ?? 'Owner Reservations Property',
            'address' => $this->buildAddress($orProperty),
            'latitude' => $orProperty['latitude'] ?? null,
            'longitude' => $orProperty['longitude'] ?? null,
            'bedrooms' => $orProperty['bedrooms'] ?? 0,
            'bathrooms' => $orProperty['bathrooms'] ?? 0,
            'square_footage' => $orProperty['square_feet'] ?? null,
            'description' => $orProperty['description'] ?? '',
            'max_occupancy' => $orProperty['max_guests'] ?? null,
            'amenities' => $orProperty['amenities'] ?? [],
            'images' => $orProperty['photos'] ?? [],
            'source' => 'owner_reservations'
        ];
    }

    /**
     * Build address string from Owner Reservations data.
     */
    protected function buildAddress(array $property): string
    {
        $parts = [];
        
        if (!empty($property['address'])) {
            $parts[] = $property['address'];
        }
        if (!empty($property['city'])) {
            $parts[] = $property['city'];
        }
        if (!empty($property['state'])) {
            $parts[] = $property['state'];
        }
        if (!empty($property['zip_code'])) {
            $parts[] = $property['zip_code'];
        }

        return implode(', ', $parts);
    }

    /**
     * Get required credential fields.
     */
    public function getRequiredCredentials(): array
    {
        return [
            'api_key' => [
                'type' => 'password',
                'label' => 'Owner Reservations API Key',
                'required' => true,
                'help' => 'Get this from your Owner Reservations account settings'
            ],
            'property_manager_id' => [
                'type' => 'text',
                'label' => 'Property Manager ID',
                'required' => false,
                'help' => 'Optional: Filter properties by manager ID'
            ]
        ];
    }

    /**
     * Get available settings.
     */
    public function getAvailableSettings(): array
    {
        return [
            'import_reservations' => [
                'type' => 'boolean',
                'label' => 'Import Reservation Data',
                'default' => false,
                'help' => 'Also import existing reservations for scheduling awareness'
            ],
            'sync_availability' => [
                'type' => 'boolean',
                'label' => 'Sync Availability Calendar',
                'default' => true,
                'help' => 'Keep property availability in sync for service scheduling'
            ]
        ];
    }
}