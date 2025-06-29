@extends('shop::customers.account.index')

@section('page_title')
    Select Service Provider
@stop

@section('account-content')
    <div class="account-content">
        <div class="booking-progress">
            <div class="progress-steps">
                <div class="step completed">Service Type</div>
                <div class="step completed">Property</div>
                <div class="step completed">Date & Time</div>
                <div class="step active">Select Provider</div>
                <div class="step">Review & Book</div>
            </div>
        </div>

        <div class="vendor-selection-container">
            <div class="selection-header">
                <h2>Available Service Providers</h2>
                <p class="text-muted">Choose from qualified providers in your area</p>
                
                <div class="service-summary">
                    <div class="summary-item">
                        <strong>Service:</strong> {{ ucfirst($bookingData['service_type']) }}
                    </div>
                    <div class="summary-item">
                        <strong>Property:</strong> {{ $property->name }}
                    </div>
                    <div class="summary-item">
                        <strong>Date:</strong> {{ \Carbon\Carbon::parse($bookingData['requested_datetime'])->format('M j, Y g:i A') }}
                    </div>
                    <div class="summary-item">
                        <strong>Priority:</strong> {{ ucfirst($bookingData['priority']) }}
                    </div>
                </div>
            </div>

            @