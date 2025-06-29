<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_services_service_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_id')->constrained('property_services_markets')->onDelete('cascade');
            $table->string('area_name', 100);
            $table->json('boundary_polygon')->nullable(); // GeoJSON polygon
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['market_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_services_service_areas');
    }
};