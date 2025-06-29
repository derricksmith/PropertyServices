<?php

namespace Webkul\PropertyServices\Console\Commands;

use Illuminate\Console\Command;
use Webkul\PropertyServices\Services\PropertyImportService;

class ImportProperties extends Command
{
    protected $signature = 'property-services:import-properties 
                            {--connection-id= : Import for specific connection ID}
                            {--provider= : Import for specific provider}
                            {--force : Force import even if not scheduled}';

    protected $description = 'Import properties from external providers';

    public function __construct(
        protected PropertyImportService $importService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting property import process...');

        try {
            if ($connectionId = $this->option('connection-id')) {
                $this->importSpecificConnection($connectionId);
            } elseif ($provider = $this->option('provider')) {
                $this->importByProvider($provider);
            } else {
                $this->importAllConnections();
            }

            $this->info('Property import process completed successfully.');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Property import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Import for specific connection.
     */
    protected function importSpecificConnection(int $connectionId): void
    {
        $connection = \Webkul\PropertyServices\Models\PropertyImportConnection::find($connectionId);
        
        if (!$connection) {
            throw new \Exception("Connection ID {$connectionId} not found");
        }

        if (!$connection->is_active && !$this->option('force')) {
            $this->warn("Skipping inactive connection {$connectionId}");
            return;
        }

        $this->info("Importing properties for connection {$connectionId} ({$connection->provider})");
        
        $results = $this->importService->importProperties($connection);
        $this->displayResults($connectionId, $results);
    }

    /**
     * Import for all connections of a specific provider.
     */
    protected function importByProvider(string $provider): void
    {
        $connections = \Webkul\PropertyServices\Models\PropertyImportConnection::where('provider', $provider)
            ->where('is_active', true)
            ->get();

        if ($connections->isEmpty()) {
            $this->warn("No active connections found for provider: {$provider}");
            return;
        }

        foreach ($connections as $connection) {
            $this->info("Importing properties for connection {$connection->id} ({$connection->provider})");
            
            $results = $this->importService->importProperties($connection);
            $this->displayResults($connection->id, $results);
        }
    }

    /**
     * Import for all scheduled connections.
     */
    protected function importAllConnections(): void
    {
        if ($this->option('force')) {
            $this->info('Force importing all active connections...');
            $results = $this->importService->importAllActiveConnections();
        } else {
            $this->info('Importing scheduled connections...');
            $results = $this->importService->importAllActiveConnections();
        }

        if (empty($results)) {
            $this->info('No connections scheduled for import at this time.');
            return;
        }

        foreach ($results as $connectionId => $result) {
            $this->displayResults($connectionId, $result);
        }
    }

    /**
     * Display import results.
     */
    protected function displayResults(int $connectionId, array $results): void
    {
        $this->table(
            ['Connection ID', 'Total', 'Imported', 'Updated', 'Skipped', 'Failed'],
            [[
                $connectionId,
                $results['total'],
                $results['imported'],
                $results['updated'],
                $results['skipped'],
                $results['failed']
            ]]
        );

        if (!empty($results['errors'])) {
            $this->warn('Errors encountered:');
            foreach ($results['errors'] as $error) {
                $this->line('  - ' . $error);
            }
        }
    }
}