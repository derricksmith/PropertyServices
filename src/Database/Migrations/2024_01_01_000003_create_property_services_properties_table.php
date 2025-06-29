<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_services_properties', function (Blueprint $table) {
            $table->id(); // unsignedBigInteger
            $table->unsignedInteger('customer_id');
            $table->unsignedBigInteger('market_id');

            $table->string('name');
            $table->text('address');
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->enum('property_type', ['rental', 'vacation', 'commercial']);
            $table->integer('square_footage')->nullable();
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->text('special_instructions')->nullable();
            $table->json('access_codes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['customer_id', 'is_active']);
            $table->index(['latitude', 'longitude']);
            $table->index('market_id');

            // Foreign Keys
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('market_id')->references('id')->on('property_services_markets');
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_services_properties');
    }
};