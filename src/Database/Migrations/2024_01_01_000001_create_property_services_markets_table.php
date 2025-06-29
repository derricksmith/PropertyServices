<?php
// packages/Webkul/PropertyServices/src/Database/Migrations/2024_01_01_000001_create_property_services_markets_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_services_markets', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2);
            $table->string('region_code', 10)->nullable();
            $table->string('city_name', 100);
            $table->string('currency_code', 3);
            $table->string('timezone', 50);
            $table->boolean('is_active')->default(true);
            $table->date('launch_date');
            $table->decimal('commission_rate', 4, 2)->default(15.00); // 15%
            $table->decimal('tax_rate', 4, 2)->default(0.00);
            $table->decimal('min_service_fee', 8, 2)->default(25.00);
            $table->decimal('emergency_multiplier', 3, 2)->default(1.50);
            $table->timestamps();
            
            $table->index(['country_code', 'region_code', 'city_name'], 'psm_country_region_city_idx');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::table('marketplace_sellers', function (Blueprint $table) {
            $table->dropColumn([
                'service_categories',
                'service_radius',
                'certifications',
                'base_hourly_rate',
                'accepts_emergency_requests',
                'availability_schedule',
                'rating_average',
                'total_reviews',
                'completed_jobs'
            ]);
        });
    }
};
