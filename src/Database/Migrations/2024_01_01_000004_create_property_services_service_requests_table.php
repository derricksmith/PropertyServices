<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_services_service_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('seller_id')->nullable();


            $table->foreignId('property_id')->constrained('property_services_properties');
            $table->foreign('seller_id')->references('id')->on('marketplace_sellers');
            $table->foreignId('market_id')->constrained('property_services_markets');
            $table->foreignId('parent_request_id')->nullable()->constrained('property_services_service_requests');

            $table->string('service_type', 100);
            $table->datetime('requested_datetime');
            $table->integer('estimated_duration')->nullable();
            $table->enum('status', ['pending', 'accepted', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->enum('priority', ['standard', 'urgent', 'emergency'])->default('standard');
            $table->boolean('is_recurring')->default(false);
            $table->json('recurring_schedule')->nullable();
            $table->text('special_requirements')->nullable();
            $table->decimal('estimated_cost', 8, 2);
            $table->decimal('actual_cost', 8, 2)->nullable();
            $table->string('currency_code', 3);
            $table->timestamps();

            // Indexes
            $table->index(['property_id', 'status']);
            $table->index(['seller_id', 'status']);
            $table->index(['market_id', 'requested_datetime'], 'pssr_market_datetime_idx');
            $table->index('status');
            $table->index('parent_request_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_services_service_requests');
    }
};
