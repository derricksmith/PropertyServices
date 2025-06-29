<?php

namespace Webkul\PropertyServices\Contracts;

use Webkul\PropertyServices\Models\PropertyImportConnection;

interface PropertyImportProvider
{
    /**
     * Test the API connection.
     */
    public function testConnection(PropertyImportConnection $connection): array;

    /**
     * Fetch properties from the external API.
     */
    public function fetchProperties(PropertyImportConnection $connection): array;

    /**
     * Get required credential fields for this provider.
     */
    public function getRequiredCredentials(): array;

    /**
     * Get available settings for this provider.
     */
    public function getAvailableSettings(): array;
}