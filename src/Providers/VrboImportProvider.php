<?php

namespace Webkul\PropertyServices\Services\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Webkul\PropertyServices\Models\PropertyImportConnection;
use Webkul\PropertyServices\Contracts\PropertyImportProvider;

class VrboImportProvider implements PropertyImportProvider
{
    protected string $baseUrl = 'https://api.vrbo.com';

    /**
     * Test the API connection.
     */
    public function testConnection(PropertyImportConnection $connection): array
    {
        try {
            $credentials = $connection->api_credentials;
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $credentials['access_token'],
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
     * Fetch properties from VRBO API.
     */
    public function fetchProperties(PropertyImportConnection $connection): array
    {
        $credentials = $connection->api_credentials;
        $properties = [];
        $page = 1;
        $limit = 50;

        do {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $credentials['access_token'],
                'Accept' => 'application/json'
            ])->get($this->baseUrl . '/v1/properties', [
                'limit' => $limit,
                'page' => $page
            ]);

            if (!$response->successful()) {
                throw new \Exception('VRBO API error: ' . $response->status());
            }

            $data = $response->json();
            $pageProperties = $data['data'] ?? [];

            foreach ($pageProperties as $property) {
                $properties[] = $this->transformPropertyData($property);
            }

            $hasMorePages = count($pageProperties) === $limit;
            $page++;

        } while ($hasMorePages);

        return $properties;
    }

    /**
     * Transform VRBO property data to our format.
     */
    protected function transformPropertyData(array $vrboProperty): array
    {
        $address = $this->buildAddress($vrboProperty['address'] ?? []);
        
        return [
            'external_id' => $vrboProperty['propertyId'],
            'name' => $vrboProperty['headline'] ?? 'VRBO Property',
            'address' => $address,
            'latitude' => $vrboProperty['geoCode']['latitude'] ?? null,
            'longitude' => $vrboProperty['geoCode']['longitude'] ?? null,
            'bedrooms' => $vrboProperty['bedrooms'] ?? 0,
            'bathrooms' => $vrboProperty['bathrooms']['full'] ?? 0,
            'square_footage' => null, // VRBO doesn't always provide this
            'description' => $vrboProperty['propertyDescription'] ?? '',
            'max_occupancy' => $vrboProperty['sleeps'] ?? null,
            'amenities' => $vrboProperty['amenities'] ?? [],
            'images' => $this->extractImages($vrboProperty['images'] ?? []),
            'source' => 'vrbo'
        ];
    }

    /**
     * Build address string from VRBO address components.
     */
    protected function buildAddress(array $addressData): string
    {
        $parts = [];
        
        if (!empty($addressData['addressLine1'])) {
            $parts[] = $addressData['addressLine1'];
        }
        if (!empty($addressData['city'])) {
            $parts[] = $addressData['city'];
        }
        if (!empty($addressData['stateProvince'])) {
            $parts[] = $addressData['stateProvince'];
        }
        if (!empty($addressData['postalCode'])) {
            $parts[] = $addressData['postalCode'];
        }
        if (!empty($addressData['countryCode'])) {
            $parts[] = $addressData['countryCode'];
        }

        return implode(', ', $parts);
    }

    /**
     * Extract image URLs from VRBO data.
     */
    protected function extractImages(array $images): array
    {
        return array_map(function ($image) {
            return $image['url'] ?? '';
        }, array_slice($images, 0, 10)); // Limit to 10 images
    }

    /**
     * Get required credential fields.
     */
    public function getRequiredCredentials(): array
    {
        return [
            'access_token' => [
                'type' => 'password',
                'label' => 'VRBO API Access Token',
                'required' => true,
                'help' => 'Get this from your VRBO Partner API dashboard'
            ],
            'client_id' => [
                'type' => 'text',
                'label' => 'Client ID',
                'required' => true,
                'help' => 'Your VRBO API Client ID'
            ]
        ];
    }

    /**
     * Get available settings.
     */
    public function getAvailableSettings(): array
    {
        return [
            'import_images' => [
                'type' => 'boolean',
                'label' => 'Import Property Images',
                'default' => true,
                'help' => 'Download and store property images locally'
            ],
            'auto_activate' => [
                'type' => 'boolean',
                'label' => 'Auto-activate Imported Properties',
                'default' => true,
                'help' => 'Automatically make imported properties available for services'
            ]
        ];
    }
}