<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('property_services_import_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->enum('provider', ['vrbo', 'owner_reservations', 'airbnb']);
            $table->text('api_credentials'); // Encrypted
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_sync_enabled')->default(true);
            $table->enum('sync_frequency', ['hourly', 'daily', 'weekly', 'manual'])->default('daily');
            $table->timestamp('last_sync_at')->nullable();
            $table->enum('last_sync_status', ['success', 'partial', 'failed', 'pending'])->nullable();
            $table->json('sync_errors')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'provider']);
            $table->index(['is_active', 'auto_sync_enabled'], 'psic_auto_sync_enabled_idx');
            $table->index('last_sync_at');
        });

        Schema::create('property_services_property_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_id');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreignId('connection_id')->constrained('property_services_import_connections')->onDelete('cascade');
            $table->foreignId('property_id')->nullable()->constrained('property_services_properties')->onDelete('set null');
            $table->string('external_id');
            $table->json('external_data');
            $table->enum('import_status', ['imported', 'updated', 'skipped', 'failed', 'pending'])->default('pending');
            $table->timestamp('last_imported_at')->nullable();
            $table->json('import_errors')->nullable();
            $table->string('duplicate_detection_hash', 64);
            $table->timestamps();

            $table->index(['customer_id', 'connection_id'], 'pspi_customer_connection_id_idx');
            $table->index(['external_id', 'connection_id'], 'pspi_external_connection_id_idx');
            $table->index('duplicate_detection_hash', 'pspi_duplicate_detection_hash_idx');
            $table->index('import_status');
            $table->index('last_imported_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('property_services_property_imports');
        Schema::dropIfExists('property_services_import_connections');
    }
};