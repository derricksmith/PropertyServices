<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('marketplace_sellers', function (Blueprint $table) {
            $table->json('service_categories')->nullable(); // cleaning, maintenance, etc.
            $table->decimal('service_radius', 5, 2)->default(10.00); // km radius
            $table->json('certifications')->nullable(); // insurance, licenses
            $table->decimal('base_hourly_rate', 8, 2)->nullable();
            $table->boolean('accepts_emergency_requests')->default(false);
            $table->json('availability_schedule')->nullable(); // weekly schedule
            $table->decimal('rating_average', 3, 2)->default(0.00);
            $table->integer('total_reviews')->default(0);
            $table->integer('completed_jobs')->default(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('marketplace_sellers');
    }
};