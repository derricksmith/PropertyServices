<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_services_seller_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('seller_id')->nullable();
            $table->foreign('seller_id')->references('id')->on('marketplace_sellers');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->boolean('is_available')->default(false);
            $table->timestamp('last_updated')->nullable();
            $table->timestamps();
            
            $table->index(['latitude', 'longitude']);
            $table->index(['is_available', 'last_updated'], 'pssl_seller_available_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_services_vendor_locations');
    }
};