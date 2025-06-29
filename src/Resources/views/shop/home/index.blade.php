@extends('shop::layouts.master')

@section('page_title')
    Property Services - Find Trusted Service Providers
@stop

@section('head')
    @parent
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=geometry,places" async defer></script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('content-wrapper')
    <div class="property-services-homepage">
        <!-- Header Section -->
        <div class="hero-section">
            <div class="container">
                <div class="hero-content">
                    <h1>Find Trusted Service Providers</h1>
                    <p class="hero-subtitle">Professional cleaning, maintenance, and property services in your area</p>
                    
                    @if($properties->isEmpty())
                        <!-- No Properties Prompt -->
                        <div class="no-properties-prompt">
                            <div class="prompt-card">
                                <i class="icon icon-home"></i>
                                <h3>Add Your First Property</h3>
                                <p>To find service providers in your area, please add your property information first.</p>
                                <a href="{{ route('shop.customer.properties.create') }}" class="btn btn-primary btn-lg">
                                    <i class="icon icon-plus"></i> Add Property
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- Service Request Form -->
                        <div class="service-request-form">
                            <form id="seller-search-form" class="search-form">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="property-select">Select Property</label>
                                        <select id="property-select" class="form-control" required>
                                            <option value="">Choose your property...</option>
                                            @foreach($properties as $property)
                                                <option value="{{ $property->id }}" 
                                                        data-lat="{{ $property->latitude }}" 
                                                        data-lng="{{ $property->longitude }}"
                                                        {{ $loop->first ? 'selected' : '' }}>
                                                    {{ $property->name }} - {{ $property->address }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="service-select">Service Type</label>
                                        <select id="service-select" class="form-control" required>
                                            <option value="">Choose service...</option>
                                            @foreach($serviceTypes as $key => $config)
                                                <option value="{{ $key }}" {{ $key === 'cleaning' ? 'selected' : '' }}>
                                                    {{ $config['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="radius-select">Search Radius</label>
                                        <select id="radius-select" class="form-control">
                                            <option value="5">5 km</option>
                                            <option value="10">10 km</option>
                                            <option value="15" selected>15 km</option>
                                            <option value="20">20 km</option>
                                            <option value="25">25 km</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="button" id="search-sellers" class="btn btn-primary">
                                            <i class="icon icon-search"></i> Find Providers
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($properties->isNotEmpty())
            <!-- Map and Results Section -->
            <div class="map-results-section">
                <div class="container-fluid">
                    <div class="row">
                        <!-- Map Column -->
                        <div class="col-lg-8">
                            <div class="map-container">
                                <div id="service-providers-map" class="service-map"></div>
                                
                                <!-- Map Controls -->
                                <div class="map-controls">
                                    <div class="map-legend">
                                        <div class="legend-item">
                                            <span class="marker property-marker"></span>
                                            <span>Your Property</span>
                                        </div>
                                        <div class="legend-item">
                                            <span class="marker seller-marker available"></span>
                                            <span>Available Provider</span>
                                        </div>
                                        <div class="legend-item">
                                            <span class="marker seller-marker busy"></span>
                                            <span>Busy Provider</span>
                                        </div>
                                    </div>
                                    
                                    <div class="map-stats" id="map-stats">
                                        <span id="seller-count">0 providers found</span>
                                        <span id="avg-distance"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Sellers List Column -->
                        <div class="col-lg-4">
                            <div class="sellers-panel">
                                <div class="panel-header">
                                    <h3>Available Service Providers</h3>
                                    <div class="sort-controls">
                                        <select id="sort-sellers" class="form-control form-control-sm">
                                            <option value="match_score">Best Match</option>
                                            <option value="distance">Closest First</option>
                                            <option value="rating">Highest Rated</option>
                                            <option value="price">Lowest Price</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="sellers-list" id="sellers-list">
                                    <div class="loading-state" id="loading-sellers">
                                        <div class="spinner"></div>
                                        <p>Finding service providers...</p>
                                    </div>
                                    
                                    <div class="no-results" id="no-sellers" style="display: none;">
                                        <i class="icon icon-alert-circle"></i>
                                        <h4>No Providers Found</h4>
                                        <p>Try expanding your search radius or selecting a different service type.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Seller Info Modal -->
    <div class="modal fade" id="seller-info-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Service Provider Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="seller-info-content">
                    <!-- Dynamic content loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="book-service-btn">Book Service</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .property-services-homepage {
            min-height: 100vh;
        }

        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 3rem 0;
        }

        .hero-content {
            text-align: center;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-content h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .no-properties-prompt {
            padding: 2rem 0;
        }

        .prompt-card {
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 1rem;
            padding: 3rem;
            backdrop-filter: blur(10px);
        }

        .prompt-card i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .prompt-card h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .service-request-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1rem;
            padding: 2rem;
            color: #333;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .form-row {
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr;
            gap: 1rem;
            align-items: end;
        }

        .form-group label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            height: 45px;
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 0 1rem;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .map-results-section {
            padding: 0;
            background: #f8f9fa;
        }

        .map-container {
            position: relative;
            height: 600px;
            background: #e9ecef;
        }

        .service-map {
            width: 100%;
            height: 100%;
            border-radius: 0;
        }

        .map-controls {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: white;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .map-legend {
            margin-bottom: 1rem;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .marker {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .property-marker {
            background: #28a745;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #28a745;
        }

        .seller-marker.available {
            background: #007bff;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #007bff;
        }

        .seller-marker.busy {
            background: #ffc107;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #ffc107;
        }

        .map-stats {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .sellers-panel {
            height: 600px;
            background: white;
            display: flex;
            flex-direction: column;
        }

        .panel-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .panel-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: #333;
        }

        .sellers-list {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .seller-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .seller-card:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
        }

        .seller-card.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .seller-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }

        .seller-name {
            font-weight: 600;
            color: #333;
            margin: 0 0 0.25rem 0;
        }

        .seller-rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.9rem;
        }

        .stars {
            color: #ffc107;
        }

        .seller-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .availability-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .available {
            background: #d4edda;
            color: #155724;
        }

        .busy {
            background: #fff3cd;
            color: #856404;
        }

        .loading-state, .no-results {
            text-align: center;
            padding: 3rem 1rem;
            color: #6c757d;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .map-results-section .row {
                flex-direction: column-reverse;
            }

            .map-container {
                height: 400px;
            }

            .sellers-panel {
                height: 400px;
            }
        }
    </style>

    <script>
        class PropertyServicesHomepage {
            constructor() {
                this.map = null;
                this.propertyMarker = null;
                this.sellerMarkers = [];
                this.selectedSeller = null;
                this.sellers = [];
                
                this.initializeEventListeners();
                this.initializeMap();
                
                // Load initial data if property is selected
                if (document.getElementById('property-select').value) {
                    this.searchSellers();
                }
            }

            initializeEventListeners() {
                document.getElementById('search-sellers').addEventListener('click', () => {
                    this.searchSellers();
                });

                document.getElementById('property-select').addEventListener('change', () => {
                    this.updateMapCenter();
                    this.searchSellers();
                });

                document.getElementById('service-select').addEventListener('change', () => {
                    if (document.getElementById('property-select').value) {
                        this.searchSellers();
                    }
                });

                document.getElementById('radius-select').addEventListener('change', () => {
                    if (document.getElementById('property-select').value) {
                        this.searchSellers();
                    }
                });

                document.getElementById('sort-sellers').addEventListener('change', (e) => {
                    this.sortSellers(e.target.value);
                });
            }

            initializeMap() {
                const mapOptions = {
                    zoom: 12,
                    center: { lat: 30.2672, lng: -97.7431 }, // Default to Austin
                    styles: [
                        {
                            featureType: "poi",
                            elementType: "labels",
                            stylers: [{ visibility: "off" }]
                        }
                    ]
                };

                this.map = new google.maps.Map(document.getElementById('service-providers-map'), mapOptions);
                
                // Set initial center if property is selected
                this.updateMapCenter();
            }

            updateMapCenter() {
                const propertySelect = document.getElementById('property-select');
                const selectedOption = propertySelect.options[propertySelect.selectedIndex];
                
                if (selectedOption && selectedOption.dataset.lat && selectedOption.dataset.lng) {
                    const lat = parseFloat(selectedOption.dataset.lat);
                    const lng = parseFloat(selectedOption.dataset.lng);
                    
                    this.map.setCenter({ lat, lng });
                    this.addPropertyMarker(lat, lng, selectedOption.text);
                }
            }

            addPropertyMarker(lat, lng, title) {
                if (this.propertyMarker) {
                    this.propertyMarker.setMap(null);
                }

                this.propertyMarker = new google.maps.Marker({
                    position: { lat, lng },
                    map: this.map,
                    title: title,
                    icon: {
                        url: 'data:image/svg+xml;base64,' + btoa(`
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                                <circle cx="16" cy="16" r="12" fill="#28a745" stroke="white" stroke-width="3"/>
                                <path d="M12 16l3 3 6-6" stroke="white" stroke-width="2" fill="none"/>
                            </svg>
                        `),
                        scaledSize: new google.maps.Size(32, 32),
                        anchor: new google.maps.Point(16, 16)
                    }
                });
            }

            async searchSellers() {
                const propertyId = document.getElementById('property-select').value;
                const serviceType = document.getElementById('service-select').value;
                const radius = document.getElementById('radius-select').value;

                if (!propertyId || !serviceType) {
                    return;
                }

                // Show loading state
                document.getElementById('loading-sellers').style.display = 'block';
                document.getElementById('no-sellers').style.display = 'none';

                try {
                    const response = await fetch('/property-services/api/nearby-sellers', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            property_id: propertyId,
                            service_type: serviceType,
                            radius: radius
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        this.sellers = data.sellers;
                        this.displaySellers(data.sellers);
                        this.addSellerMarkers(data.sellers);
                        this.updateMapStats(data.sellers);
                    } else {
                        throw new Error(data.message || 'Failed to fetch sellers');
                    }
                } catch (error) {
                    console.error('Error fetching sellers:', error);
                    this.showNoResults();
                } finally {
                    document.getElementById('loading-sellers').style.display = 'none';
                }
            }

            displaySellers(sellers) {
                const sellersList = document.getElementById('sellers-list');
                
                if (sellers.length === 0) {
                    this.showNoResults();
                    return;
                }

                const sellersHTML = sellers.map(seller => `
                    <div class="seller-card" data-seller-id="${seller.id}" onclick="propertyServicesHomepage.selectSeller(${seller.id})">
                        <div class="seller-header">
                            <div class="seller-info">
                                <h4 class="seller-name">${seller.name}</h4>
                                <div class="seller-rating">
                                    <span class="stars">${this.generateStars(seller.rating)}</span>
                                    <span>${seller.rating.toFixed(1)} (${seller.total_reviews})</span>
                                </div>
                            </div>
                            <div class="availability-badge ${seller.is_available ? 'available' : 'busy'}">
                                ${seller.is_available ? 'Available' : 'Busy'}
                            </div>
                        </div>
                        
                        <div class="seller-stats">
                            <div class="stat-item">
                                <i class="icon icon-map-pin"></i>
                                <span>${seller.distance} km away</span>
                            </div>
                            <div class="stat-item">
                                <i class="icon icon-clock"></i>
                                <span>ETA: ${seller.estimated_arrival} min</span>
                            </div>
                            <div class="stat-item">
                                <i class="icon icon-award"></i>
                                <span>${seller.completed_jobs} jobs completed</span>
                            </div>
                            <div class="stat-item">
                                <i class="icon icon-dollar-sign"></i>
                                <span>From ${seller.base_hourly_rate || 'Contact'}/hr</span>
                            </div>
                        </div>

                        ${seller.accepts_emergency ? '<div class="emergency-badge">Emergency Services Available</div>' : ''}
                    </div>
                `).join('');

                sellersList.innerHTML = sellersHTML;
            }

            addSellerMarkers(sellers) {
                // Clear existing markers
                this.sellerMarkers.forEach(marker => marker.setMap(null));
                this.sellerMarkers = [];

                sellers.forEach(seller => {
                    const marker = new google.maps.Marker({
                        position: { lat: seller.latitude, lng: seller.longitude },
                        map: this.map,
                        title: `${seller.name} - ${seller.distance}km away`,
                        icon: {
                            url: 'data:image/svg+xml;base64,' + btoa(`
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
                                    <circle cx="16" cy="16" r="12" fill="${seller.is_available ? '#007bff' : '#ffc107'}" stroke="white" stroke-width="3"/>
                                    <text x="16" y="20" text-anchor="middle" fill="white" font-size="12" font-weight="bold">S</text>
                                </svg>
                            `),
                            scaledSize: new google.maps.Size(32, 32),
                            anchor: new google.maps.Point(16, 16)
                        }
                    });

                    // Add click listener to marker
                    marker.addListener('click', () => {
                        this.selectSeller(seller.id);
                        this.showSellerInfo(seller);
                    });

                    this.sellerMarkers.push(marker);
                });

                // Adjust map bounds to show all markers
                if (sellers.length > 0) {
                    const bounds = new google.maps.LatLngBounds();
                    
                    // Include property marker
                    if (this.propertyMarker) {
                        bounds.extend(this.propertyMarker.getPosition());
                    }
                    
                    // Include all seller markers
                    this.sellerMarkers.forEach(marker => {
                        bounds.extend(marker.getPosition());
                    });
                    
                    this.map.fitBounds(bounds);
                    
                    // Ensure minimum zoom level
                    if (this.map.getZoom() > 15) {
                        this.map.setZoom(15);
                    }
                }
            }

            selectSeller(sellerId) {
                // Remove previous selection
                document.querySelectorAll('.seller-card').forEach(card => {
                    card.classList.remove('selected');
                });

                // Add selection to clicked card
                const selectedCard = document.querySelector(`[data-seller-id="${sellerId}"]`);
                if (selectedCard) {
                    selectedCard.classList.add('selected');
                }

                this.selectedSeller = this.sellers.find(seller => seller.id === sellerId);

                // Highlight corresponding marker
                this.highlightSellerMarker(sellerId);
            }

            highlightSellerMarker(sellerId) {
                const seller = this.sellers.find(s => s.id === sellerId);
                if (!seller) return;

                const markerIndex = this.sellers.indexOf(seller);
                if (markerIndex >= 0 && this.sellerMarkers[markerIndex]) {
                    // Center map on selected marker
                    this.map.setCenter({
                        lat: seller.latitude,
                        lng: seller.longitude
                    });

                    // Animate marker
                    const marker = this.sellerMarkers[markerIndex];
                    marker.setAnimation(google.maps.Animation.BOUNCE);
                    setTimeout(() => marker.setAnimation(null), 2000);
                }
            }

            showSellerInfo(seller) {
                const modalContent = document.getElementById('seller-info-content');
                modalContent.innerHTML = `
                    <div class="seller-details">
                        <div class="seller-header">
                            <h3>${seller.name}</h3>
                            <div class="seller-rating">
                                <span class="stars">${this.generateStars(seller.rating)}</span>
                                <span>${seller.rating.toFixed(1)} out of 5 (${seller.total_reviews} reviews)</span>
                            </div>
                        </div>

                        <div class="seller-stats-grid">
                            <div class="stat-card">
                                <i class="icon icon-map-pin"></i>
                                <div>
                                    <strong>${seller.distance} km</strong>
                                    <small>Distance</small>
                                </div>
                            </div>
                            <div class="stat-card">
                                <i class="icon icon-clock"></i>
                                <div>
                                    <strong>${seller.estimated_arrival} min</strong>
                                    <small>ETA</small>
                                </div>
                            </div>
                            <div class="stat-card">
                                <i class="icon icon-award"></i>
                                <div>
                                    <strong>${seller.completed_jobs}</strong>
                                    <small>Jobs Completed</small>
                                </div>
                            </div>
                            <div class="stat-card">
                                <i class="icon icon-dollar-sign"></i>
                                <div>
                                    <strong>${seller.base_hourly_rate || 'Contact'}</strong>
                                    <small>Per Hour</small>
                                </div>
                            </div>
                        </div>

                        <div class="service-categories">
                            <h4>Services Offered</h4>
                            <div class="categories-list">
                                ${seller.service_categories.map(category => `
                                    <span class="category-badge">${this.formatServiceName(category)}</span>
                                `).join('')}
                            </div>
                        </div>

                        ${seller.accepts_emergency ? `
                            <div class="emergency-services">
                                <i class="icon icon-alert-circle"></i>
                                <span>Emergency services available</span>
                            </div>
                        ` : ''}
                    </div>
                `;

                // Update book service button
                const bookBtn = document.getElementById('book-service-btn');
                bookBtn.onclick = () => this.startBooking(seller.id);

                // Show modal
                $('#seller-info-modal').modal('show');
            }

            startBooking(sellerId = null) {
                const propertyId = document.getElementById('property-select').value;
                const serviceType = document.getElementById('service-select').value;

                if (!propertyId || !serviceType) {
                    alert('Please select a property and service type first.');
                    return;
                }

                const params = new URLSearchParams({
                    property_id: propertyId,
                    service_type: serviceType
                });

                if (sellerId) {
                    params.append('preferred_seller_id', sellerId);
                }

                window.location.href = `/customer/booking/datetime?${params.toString()}`;
            }

            sortSellers(sortBy) {
                let sortedSellers = [...this.sellers];

                switch (sortBy) {
                    case 'distance':
                        sortedSellers.sort((a, b) => a.distance - b.distance);
                        break;
                    case 'rating':
                        sortedSellers.sort((a, b) => b.rating - a.rating);
                        break;
                    case 'price':
                        sortedSellers.sort((a, b) => (a.base_hourly_rate || 999) - (b.base_hourly_rate || 999));
                        break;
                    case 'match_score':
                    default:
                        sortedSellers.sort((a, b) => b.match_score - a.match_score);
                        break;
                }

                this.sellers = sortedSellers;
                this.displaySellers(sortedSellers);
                this.addSellerMarkers(sortedSellers);
            }

            updateMapStats(sellers) {
                const sellerCount = sellers.length;
                const avgDistance = sellers.length > 0 
                    ? (sellers.reduce((sum, seller) => sum + seller.distance, 0) / sellers.length).toFixed(1)
                    : 0;

                document.getElementById('seller-count').textContent = `${sellerCount} provider${sellerCount !== 1 ? 's' : ''} found`;
                document.getElementById('avg-distance').textContent = sellerCount > 0 ? `Avg distance: ${avgDistance} km` : '';
            }

            showNoResults() {
                document.getElementById('no-sellers').style.display = 'block';
                document.getElementById('sellers-list').innerHTML = '';
                
                // Clear markers
                this.sellerMarkers.forEach(marker => marker.setMap(null));
                this.sellerMarkers = [];
                
                this.updateMapStats([]);
            }

            generateStars(rating) {
                const fullStars = Math.floor(rating);
                const hasHalfStar = rating % 1 >= 0.5;
                const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

                return '★'.repeat(fullStars) + 
                       (hasHalfStar ? '☆' : '') + 
                       '☆'.repeat(emptyStars);
            }

            formatServiceName(serviceName) {
                return serviceName.replace(/_/g, ' ')
                    .split(' ')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                    .join(' ');
            }
        }

        // Global variable for access from onclick handlers
        let propertyServicesHomepage;

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof google !== 'undefined' && google.maps) {
                propertyServicesHomepage = new PropertyServicesHomepage();
            } else {
                // Wait for Google Maps to load
                window.initPropertyServicesMap = function() {
                    propertyServicesHomepage = new PropertyServicesHomepage();
                };
            }
        });

        // Quick booking function for cards
        function quickBook(sellerId) {
            if (propertyServicesHomepage) {
                propertyServicesHomepage.startBooking(sellerId);
            }
        }
    </script>

    <style>
        .seller-details {
            padding: 1rem 0;
        }

        .seller-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .seller-header h3 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }

        .seller-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
        }

        .stat-card i {
            font-size: 1.5rem;
            color: #667eea;
        }

        .stat-card strong {
            display: block;
            font-size: 1.1rem;
            color: #333;
        }

        .stat-card small {
            color: #6c757d;
            font-size: 0.85rem;
        }

        .service-categories {
            margin-bottom: 2rem;
        }

        .service-categories h4 {
            margin-bottom: 1rem;
            color: #333;
        }

        .categories-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .category-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .emergency-services {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 0.5rem;
            padding: 0.75rem;
            color: #856404;
            font-weight: 500;
        }

        .emergency-badge {
            background: #dc3545;
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        /* Additional responsive styles */
        @media (max-width: 992px) {
            .seller-stats-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .categories-list {
                flex-direction: column;
            }

            .category-badge {
                text-align: center;
            }
        }
    </style>
@stop
