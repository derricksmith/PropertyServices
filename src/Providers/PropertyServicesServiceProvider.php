<?php
// packages/Webkul/PropertyServices/src/Providers/PropertyServicesServiceProvider.php

namespace Webkul\PropertyServices\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Console\Scheduling\Schedule;
use Webkul\PropertyServices\Console\Commands\ImportProperties;
use Webkul\PropertyServices\Console\Commands\CleanupImportData;
use Webkul\PropertyServices\Console\Commands\InstallPropertyServices;
use Webkul\PropertyServices\Services\PropertyImportService;

class PropertyServicesServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();
        $this->registerCommands();

        // Register import service
        $this->app->singleton(PropertyImportService::class);
        
        // Register import providers
        $this->registerImportProviders();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin-routes.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/shop-routes.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api-routes.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/scheduler.php');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'property_services');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'property_services');

        $this->publishes([
            __DIR__ . '/../Resources/views' => resource_path('themes/default/views/property_services'),
        ], 'bagisto-property-services-views');

        $this->publishes([
            __DIR__ . '/../Resources/assets' => public_path('themes/default/assets/property_services'),
        ], 'bagisto-property-services-assets');

        $this->publishes([
            __DIR__ . '/../Config/property_services.php' => config_path('property_services.php'),
        ], 'bagisto-property-services-config');

        $this->composeView();

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                ImportProperties::class,
                CleanupImportData::class,
            ]);
        }

        // Register scheduled tasks
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $this->defineSchedule($schedule);
        });
    }

    /**
     * Register package config.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/menu.php', 'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/acl.php', 'acl'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/property_services.php', 'property_services'
        );
    }

    /**
     * Register console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallPropertyServices::class,
                ImportProperties::class,
            ]);
        }
    }

    /**
     * Bind the the data that is passed to the admin layout.
     */
    protected function composeView(): void
    {
        view()->composer(['property_services::admin.*'], function ($view) {
            $view->with('admin', auth()->guard('admin')->user());
        });
    }

    /**
     * Register import providers.
     */
    protected function registerImportProviders(): void
    {
        $this->app->bind(
            \Webkul\PropertyServices\Contracts\PropertyImportProvider::class,
            \Webkul\PropertyServices\Services\Providers\VrboImportProvider::class
        );
    }

    /**
     * Define scheduled tasks.
     */
    protected function defineSchedule(Schedule $schedule): void
    {
        // Import properties for all scheduled connections
        $schedule->command('property-services:import-properties')
            ->everyFifteenMinutes()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/property-import.log'));

        // Cleanup old import data
        $schedule->command('property-services:cleanup-import-data')
            ->daily()
            ->at('02:00')
            ->appendOutputTo(storage_path('logs/property-import-cleanup.log'));
    }
}
