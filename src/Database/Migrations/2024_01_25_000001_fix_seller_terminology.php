<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Rename vendor_locations table to seller_locations
        if (Schema::hasTable('property_services_vendor_locations')) {
            Schema::rename('property_services_vendor_locations', 'property_services_seller_locations');
        }

        // Update seller_locations table structure
        Schema::table('property_services_seller_locations', function (Blueprint $table) {
            if (Schema::hasColumn('property_services_seller_locations', 'vendor_id')) {
                $table->renameColumn('vendor_id', 'seller_id');
            }
            
            // Update foreign key constraint
            $table->dropForeign(['seller_id']);
            $table->foreign('seller_id')->references('id')->on('marketplace_sellers')->onDelete('cascade');
        });

        // Update service_requests table
        Schema::table('property_services_service_requests', function (Blueprint $table) {
            if (Schema::hasColumn('property_services_service_requests', 'vendor_id')) {
                $table->renameColumn('vendor_id', 'seller_id');
            }
            
            // Update foreign key constraint
            $table->dropForeign(['seller_id']);
            $table->foreign('seller_id')->nullable()->references('id')->on('marketplace_sellers')->onDelete('set null');
        });

        // Update any other tables that reference vendors
        if (Schema::hasTable('property_services_seller_reviews')) {
            Schema::table('property_services_seller_reviews', function (Blueprint $table) {
                if (Schema::hasColumn('property_services_seller_reviews', 'vendor_id')) {
                    $table->renameColumn('vendor_id', 'seller_id');
                    $table->dropForeign(['seller_id']);
                    $table->foreign('seller_id')->references('id')->on('marketplace_sellers')->onDelete('cascade');
                }
            });
        }
    }

    public function down()
    {
        // Reverse the changes
        Schema::table('property_services_service_requests', function (Blueprint $table) {
            if (Schema::hasColumn('property_services_service_requests', 'seller_id')) {
                $table->renameColumn('seller_id', 'vendor_id');
            }
        });

        Schema::table('property_services_seller_locations', function (Blueprint $table) {
            if (Schema::hasColumn('property_services_seller_locations', 'seller_id')) {
                $table->renameColumn('seller_id', 'vendor_id');
            }
        });

        if (Schema::hasTable('property_services_seller_locations')) {
            Schema::rename('property_services_seller_locations', 'property_services_vendor_locations');
        }
    }
};