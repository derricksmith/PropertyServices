<?php

namespace Webkul\PropertyServices\Console\Commands;

use Illuminate\Console\Command;
use Webkul\PropertyServices\Models\PropertyImportConnection;
use Webkul\PropertyServices\Models\PropertyImport;

class CleanupImportData extends Command
{
    protected $signature = 'property-services:cleanup-import-data 
                            {--days=30 : Delete import records older than specified days}
                            {--dry-run : Show what would be deleted without actually deleting}';

    protected $description = 'Clean up old import data and logs';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $cutoffDate = now()->subDays($days);

        $this->info("Cleaning up import data older than {$days} days (before {$cutoffDate->format('Y-m-d H:i:s')})");

        // Clean up old sync errors
        $connectionsToClean = PropertyImportConnection::whereNotNull('sync_errors')
            ->where('last_sync_at', '<', $cutoffDate)
            ->count();

        if ($connectionsToClean > 0) {
            $this->info("Found {$connectionsToClean} connections with old sync errors to clean");
            
            if (!$dryRun) {
                PropertyImportConnection::whereNotNull('sync_errors')
                    ->where('last_sync_at', '<', $cutoffDate)
                    ->update(['sync_errors' => null]);
                
                $this->info("Cleaned sync errors from {$connectionsToClean} connections");
            }
        }

        // Clean up failed import records
        $failedImports = PropertyImport::where('import_status', 'failed')
            ->where('last_imported_at', '<', $cutoffDate)
            ->count();

        if ($failedImports > 0) {
            $this->info("Found {$failedImports} failed import records to clean");
            
            if (!$dryRun) {
                PropertyImport::where('import_status', 'failed')
                    ->where('last_imported_at', '<', $cutoffDate)
                    ->delete();
                
                $this->info("Deleted {$failedImports} failed import records");
            }
        }

        // Clean up orphaned import records (where property was deleted)
        $orphanedImports = PropertyImport::whereDoesntHave('property')->count();

        if ($orphanedImports > 0) {
            $this->info("Found {$orphanedImports} orphaned import records to clean");
            
            if (!$dryRun) {
                PropertyImport::whereDoesntHave('property')->delete();
                $this->info("Deleted {$orphanedImports} orphaned import records");
            }
        }

        if ($dryRun) {
            $this->info('Dry run completed. Use --dry-run=false to actually clean the data.');
        } else {
            $this->info('Import data cleanup completed successfully.');
        }

        return Command::SUCCESS;
    }
}