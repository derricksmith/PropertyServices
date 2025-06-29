@extends('shop::customers.account.index')

@section('page_title')
    {{ __('property_services::app.shop.customer.properties.add-property') }}
@stop

@section('account-content')
    <div class="account-content">
        <div class="account-layout">
            <div class="account-head">
                <span class="back-icon">
                    <a href="{{ route('shop.customer.properties.index') }}">
                        <i class="icon icon-menu-back"></i>
                    </a>
                </span>

                <span class="account-heading">
                    {{ __('property_services::app.shop.customer.properties.add-property') }}
                </span>
            </div>

            <form action="{{ route('shop.customer.properties.store') }}" method="POST" class="account-form">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="control-group">
                            <label for="name" class="required">Property Name</label>
                            <input type="text" name="name" id="name" class="control" value="{{ old('name') }}" required>
                            @error('name')
                                <span class="control-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="control-group">
                            <label for="market_id" class="required">Market</label>
                            <select name="market_id" id="market_id" class="control" required>
                                <option value="">Select Market</option>
                                @foreach($markets as $market)
                                    <option value="{{ $market->id }}" {{ old('market_id') == $market->id ? 'selected' : '' }}>
                                        {{ $market->city_name }}, {{ $market->region_code }}
                                    </option>
                                @endforeach
                            </select>
                            @error('market_id')
                                <span class="control-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="control-group">
                    <label for="address" class="required">Address</label>
                    <textarea name="address" id="address" class="control" rows="3" required>{{ old('address') }}</textarea>
                    @error('address')
                        <span class="control-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="control-group">
                            <label for="latitude" class="required">Latitude</label>
                            <input type="number" step="any" name="latitude" id="latitude" class="control" value="{{ old('latitude') }}" required>
                            @error('latitude')
                                <span class="control-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="control-group">
                            <label for="longitude" class="required">Longitude</label>
                            <input type="number" step="any" name="longitude" id="longitude" class="control" value="{{ old('longitude') }}" required>
                            @error('longitude')
                                <span class="control-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="control-group">
                            <label for="property_type" class="required">Property Type</label>
                            <select name="property_type" id="property_type" class="control" required>
                                <option value="">Select Type</option>
                                <option value="rental" {{ old('property_type') == 'rental' ? 'selected' : '' }}>Rental</option>
                                <option value="vacation" {{ old('property_type') == 'vacation' ? 'selected' : '' }}>Vacation Rental</option>
                                <option value="commercial" {{ old('property_type') == 'commercial' ? 'selected' : '' }}>Commercial</option>
                            </select>
                            @error('property_type')
                                <span class="control-error">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="control-group">
                            <label for="bedrooms">Bedrooms</label>
                            <input type="number" name="bedrooms" id="bedrooms" class="control" value="{{ old('bedrooms') }}" min="0">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="control-group">
                            <label for="bathrooms">Bathrooms</label>
                            <input type="number" name="bathrooms" id="bathrooms" class="control" value="{{ old('bathrooms') }}" min="0">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="control-group">
                            <label for="square_footage">Square Footage</label>
                            <input type="number" name="square_footage" id="square_footage" class="control" value="{{ old('square_footage') }}" min="1">
                        </div>
                    </div>
                </div>

                <div class="control-group">
                    <label for="special_instructions">Special Instructions</label>
                    <textarea name="special_instructions" id="special_instructions" class="control" rows="4" placeholder="Any special access instructions, cleaning requirements, etc.">{{ old('special_instructions') }}</textarea>
                </div>

                <div class="button-group">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Add Property
                    </button>
                    
                    <a href="{{ route('shop.customer.properties.index') }}" class="btn btn-secondary btn-lg">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add location picker functionality
        document.addEventListener('DOMContentLoaded', function() {
            const addressField = document.getElementById('address');
            const latField = document.getElementById('latitude');
            const lngField = document.getElementById('longitude');

            // Add Google Maps integration here if available
            // This would allow customers to pick location on map
            // and auto-fill lat/lng coordinates
        });
    </script>
@stop
