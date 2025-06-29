<?php
// packages/Webkul/PropertyServices/src/Http/Controllers/Shop/PropertyImportController.php

namespace Webkul\PropertyServices\Http\Controllers\Shop;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Webkul\Shop\Http\Controllers\Controller;
use Webkul\PropertyServices\Models\PropertyImportConnection;
use Webkul\PropertyServices\Models\PropertyImport;
use Webkul\PropertyServices\Services\PropertyImportService;

class PropertyImportController extends Controller
{
    public function __construct(
        protected PropertyImportService $importService
    ) {}

    /**
     * Display import settings and connections.
     */
    public function index(): View
    {
        $connections = PropertyImportConnection::where('customer_id', auth('customer')->id())
            ->with('importedProperties')
            ->withCount('importedProperties')
            ->orderBy('created_at', 'desc')
            ->get();

        $availableProviders = PropertyImportConnection::PROVIDERS;

        return view('property_services::shop.customer.imports.index', compact('connections', 'availableProviders'));
    }

    /**
     * Show form to create new import connection.
     */
    public function create(Request $request): View
    {
        $provider = $request->get('provider');
        
        if (!array_key_exists($provider, PropertyImportConnection::PROVIDERS)) {
            abort(404, 'Invalid provider');
        }

        $providerService = $this->importService->getProvider($provider);
        $requiredCredentials = $providerService->getRequiredCredentials();
        $availableSettings = $providerService->getAvailableSettings();

        return view('property_services::shop.customer.imports.create', compact(
            'provider', 'requiredCredentials', 'availableSettings'
        ));
    }

    /**
     * Store new import connection.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'provider' => 'required|in:' . implode(',', array_keys(PropertyImportConnection::PROVIDERS)),
            'credentials' => 'required|array',
            'sync_frequency' => 'required|in:' . implode(',', array_keys(PropertyImportConnection::SYNC_FREQUENCIES)),
            'auto_sync_enabled' => 'boolean',
            'settings' => 'array'
        ]);

        // Validate provider-specific credentials
        $provider = $this->importService->getProvider($request->provider);
        $requiredCredentials = $provider->getRequiredCredentials();

        foreach ($requiredCredentials as $field => $config) {
            if ($config['required'] && empty($request->credentials[$field])) {
                return redirect()->back()
                    ->withErrors([$field => "The {$config['label']} field is required."])
                    ->withInput();
            }
        }

        $connection = PropertyImportConnection::create([
            'customer_id' => auth('customer')->id(),
            'provider' => $request->provider,
            'api_credentials' => $request->credentials,
            'sync_frequency' => $request->sync_frequency,
            'auto_sync_enabled' => $request->boolean('auto_sync_enabled'),
            'settings' => $request->settings ?? [],
            'is_active' => false // Will be activated after successful test
        ]);

        // Test the connection
        $testResult = $connection->testConnection();

        if ($testResult['success']) {
            $connection->update(['is_active' => true]);
            session()->flash('success', 'Import connection created and tested successfully!');
        } else {
            session()->flash('warning', 'Connection created but test failed: ' . $testResult['message']);
        }

        return redirect()->route('shop.customer.imports.show', $connection);
    }

    /**
     * Show import connection details.
     */
    public function show(PropertyImportConnection $connection): View
    {
        // Ensure customer can only view their own connections
        if ($connection->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        $connection->load([
            'importedProperties.property',
            'importedProperties' => function ($query) {
                $query->orderBy('last_imported_at', 'desc');
            }
        ]);

        $recentImports = $connection->importedProperties()->limit(10)->get();

        return view('property_services::shop.customer.imports.show', compact('connection', 'recentImports'));
    }

    /**
     * Edit import connection settings.
     */
    public function edit(PropertyImportConnection $connection): View
    {
        if ($connection->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        $provider = $this->importService->getProvider($connection->provider);
        $requiredCredentials = $provider->getRequiredCredentials();
        $availableSettings = $provider->getAvailableSettings();

        return view('property_services::shop.customer.imports.edit', compact(
            'connection', 'requiredCredentials', 'availableSettings'
        ));
    }

    /**
     * Update import connection.
     */
    public function update(Request $request, PropertyImportConnection $connection): RedirectResponse
    {
        if ($connection->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        $request->validate([
            'credentials' => 'required|array',
            'sync_frequency' => 'required|in:' . implode(',', array_keys(PropertyImportConnection::SYNC_FREQUENCIES)),
            'auto_sync_enabled' => 'boolean',
            'settings' => 'array'
        ]);

        $connection->update([
            'api_credentials' => $request->credentials,
            'sync_frequency' => $request->sync_frequency,
            'auto_sync_enabled' => $request->boolean('auto_sync_enabled'),
            'settings' => $request->settings ?? []
        ]);

        session()->flash('success', 'Import connection updated successfully!');

        return redirect()->route('shop.customer.imports.show', $connection);
    }

    /**
     * Delete import connection.
     */
    public function destroy(PropertyImportConnection $connection): RedirectResponse
    {
        if ($connection->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        // Check if there are imported properties
        if ($connection->importedProperties()->exists()) {
            session()->flash('error', 'Cannot delete connection with imported properties. Please remove imported properties first.');
            return redirect()->back();
        }

        $connection->delete();

        session()->flash('success', 'Import connection deleted successfully.');

        return redirect()->route('shop.customer.imports.index');
    }

    /**
     * Test import connection.
     */
    public function testConnection(PropertyImportConnection $connection): RedirectResponse
    {
        if ($connection->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        $result = $connection->testConnection();

        if ($result['success']) {
            session()->flash('success', 'Connection test successful: ' . $result['message']);
        } else {
            session()->flash('error', 'Connection test failed: ' . $result['message']);
        }

        return redirect()->back();
    }

    /**
     * Manually trigger import for a connection.
     */
    public function manualImport(PropertyImportConnection $connection): RedirectResponse
    {
        if ($connection->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        if (!$connection->is_active) {
            session()->flash('error', 'Cannot import from inactive connection.');
            return redirect()->back();
        }

        try {
            $results = $this->importService->importProperties($connection);

            $message = sprintf(
                'Import completed: %d total, %d imported, %d updated, %d skipped, %d failed',
                $results['total'],
                $results['imported'],
                $results['updated'],
                $results['skipped'],
                $results['failed']
            );

            if ($results['failed'] > 0) {
                session()->flash('warning', $message);
            } else {
                session()->flash('success', $message);
            }

        } catch (\Exception $e) {
            session()->flash('error', 'Import failed: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    /**
     * Toggle connection active status.
     */
    public function toggleActive(PropertyImportConnection $connection): RedirectResponse
    {
        if ($connection->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        $connection->update(['is_active' => !$connection->is_active]);

        $status = $connection->is_active ? 'activated' : 'deactivated';
        session()->flash('success', "Import connection {$status} successfully.");

        return redirect()->back();
    }

    /**
     * Show import history for customer.
     */
    public function history(): View
    {
        $imports = PropertyImport::where('customer_id', auth('customer')->id())
            ->with(['connection', 'property'])
            ->orderBy('last_imported_at', 'desc')
            ->paginate(20);

        return view('property_services::shop.customer.imports.history', compact('imports'));
    }

    /**
     * Remove an imported property.
     */
    public function removeImportedProperty(PropertyImport $import): RedirectResponse
    {
        if ($import->customer_id !== auth('customer')->id()) {
            abort(403);
        }

        // Check if property has active service requests
        if ($import->property && $import->property->serviceRequests()->whereIn('status', ['pending', 'accepted', 'in_progress'])->exists()) {
            session()->flash('error', 'Cannot remove property with active service requests.');
            return redirect()->back();
        }

        // Delete the property if it exists
        if ($import->property) {
            $import->property->delete();
        }

        // Delete the import record
        $import->delete();

        session()->flash('success', 'Imported property removed successfully.');

        return redirect()->back();
    }
}