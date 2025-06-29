<?php

namespace Webkul\PropertyServices\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallPropertyServices extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'property-services:install 
                            {--force : Force installation even if already installed}';

    /**
     * The console command description.
     */
    protected $description = 'Install PropertyServices package for Bagisto 2.3-dev';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Installing PropertyServices package...');

        // Check Bagisto version compatibility
        if (!$this->checkBagistoVersion()) {
            $this->error('PropertyServices requires Bagisto 2.3 or higher');
            return Command::FAILURE;
        }

        // Run migrations
        $this->info('Running migrations...');
        Artisan::call('migrate', ['--path' => 'packages/Webkul/PropertyServices/src/Database/Migrations']);
        $this->info(Artisan::output());

        // Publish assets
        $this->info('Publishing assets...');
        Artisan::call('vendor:publish', [
            '--tag' => 'bagisto-property-services-assets',
            '--force' => $this->option('force')
        ]);

        // Publish configuration
        $this->info('Publishing configuration...');
        Artisan::call('vendor:publish', [
            '--tag' => 'bagisto-property-services-config',
            '--force' => $this->option('force')
        ]);

        // Seed initial data
        if ($this->confirm('Would you like to seed initial market data?', true)) {
            $this->info('Seeding initial data...');
            Artisan::call('db:seed', [
                '--class' => 'Webkul\\PropertyServices\\Database\\Seeders\\PropertyServicesSeeder'
            ]);
        }

        // Clear caches
        $this->info('Clearing caches...');
        Artisan::call('config:clear');
        Artisan::call('cache:clear');
        Artisan::call('view:clear');

        $this->info('✅ PropertyServices package installed successfully!');
        $this->line('');
        $this->line('Next steps:');
        $this->line('1. Configure your Google Maps API key in .env');
        $this->line('2. Set up Pusher for real-time features');
        $this->line('3. Configure Stripe Connect for payments');
        $this->line('4. Visit /admin/property-services to access the admin panel');

        return Command::SUCCESS;
    }

    /**
     * Check if Bagisto version is compatible.
     */
    private function checkBagistoVersion(): bool
    {
        try {
            $version = config('bagisto.version', '2.3.0');
            return version_compare($version, '2.3', '>=');
        } catch (\Exception $e) {
            return true; // Assume compatible if version check fails
        }
    }
}
