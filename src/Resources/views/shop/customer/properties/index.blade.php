@extends('shop::customers.account.index')

@section('page_title')
    {{ __('property_services::app.shop.customer.properties.title') }}
@stop

@section('account-content')
    <div class="account-content">
        <div class="account-layout">
            <div class="account-head">
                <span class="back-icon">
                    <a href="{{ route('customer.account.index') }}">
                        <i class="icon icon-menu-back"></i>
                    </a>
                </span>

                <span class="account-heading">
                    {{ __('property_services::app.shop.customer.properties.title') }}
                </span>

                <span class="account-action">
                    <a href="{{ route('shop.customer.properties.create') }}" class="btn btn-primary">
                        {{ __('property_services::app.shop.customer.properties.add-property') }}
                    </a>
                </span>
            </div>

            <div class="account-items-list">
                @if($properties->count())
                    @foreach($properties as $property)
                        <div class="account-item-card">
                            <div class="media-info">
                                <div class="info">
                                    <div class="property-name">
                                        <a href="{{ route('shop.customer.properties.show', $property) }}">
                                            {{ $property->name }}
                                        </a>
                                    </div>
                                    
                                    <div class="property-details">
                                        <span class="property-type">{{ ucfirst($property->property_type) }}</span>
                                        @if($property->bedrooms || $property->bathrooms)
                                            <span class="property-rooms">
                                                @if($property->bedrooms) {{ $property->bedrooms }}BR @endif
                                                @if($property->bathrooms) {{ $property->bathrooms }}BA @endif
                                            </span>
                                        @endif
                                        <span class="property-market">{{ $property->market->city_name }}, {{ $property->market->region_code }}</span>
                                    </div>
                                    
                                    <div class="property-address">
                                        {{ $property->address }}
                                    </div>

                                    @if($property->serviceRequests->count())
                                        <div class="recent-services">
                                            <strong>Recent Services:</strong>
                                            @foreach($property->serviceRequests as $request)
                                                <span class="service-badge badge-{{ $request->status }}">
                                                    {{ ucfirst($request->service_type) }} - {{ ucfirst($request->status) }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="operations">
                                <a href="{{ route('shop.customer.properties.show', $property) }}" class="btn btn-sm btn-primary">
                                    View
                                </a>
                                
                                <a href="{{ route('shop.customer.properties.services.create', $property) }}" class="btn btn-sm btn-success">
                                    Request Service
                                </a>
                                
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                        More
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{ route('shop.customer.properties.edit', $property) }}">Edit</a>
                                        <form action="{{ route('shop.customer.properties.destroy', $property) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure?')">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="pagination-wrapper">
                        {{ $properties->links() }}
                    </div>
                @else
                    <div class="empty-properties">
                        <div class="empty-info">
                            <h3>No Properties Added Yet</h3>
                            <p>Add your first property to start requesting cleaning and maintenance services.</p>
                            <a href="{{ route('shop.customer.properties.create') }}" class="btn btn-primary">
                                Add Your First Property
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@stop
