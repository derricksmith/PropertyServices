<template>
    <div class="service-matching-interface">
        <div class="map-container">
            <div id="service-map" style="height: 400px;"></div>
        </div>
        
        <div class="vendor-list">
            <h3>Available Service Providers</h3>
            <div v-for="vendor in matchedVendors" :key="vendor.vendor_id" class="vendor-card">
                <div class="vendor-info">
                    <h4>{{ vendor.vendor.shop_title }}</h4>
                    <div class="vendor-rating">
                        <span class="rating-stars">★★★★☆</span>
                        <span class="rating-score">{{ vendor.vendor.rating_average }}/5</span>
                        <span class="rating-count">({{ vendor.vendor.total_reviews }} reviews)</span>
                    </div>
                    <div class="vendor-distance">
                        {{ vendor.location.distance.toFixed(1) }} km away
                    </div>
                    <div class="vendor-eta">
                        ETA: {{ vendor.estimated_arrival }} minutes
                    </div>
                </div>
                
                <div class="vendor-actions">
                    <button @click="selectVendor(vendor)" class="btn btn-primary">
                        Select Provider
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ServiceMatching',
    data() {
        return {
            matchedVendors: [],
            selectedProperty: null,
            serviceType: null,
            map: null
        };
    },
    
    mounted() {
        this.initializeMap();
        this.loadMatchedVendors();
    },
    
    methods: {
        initializeMap() {
            // Initialize Google Maps
            this.map = new google.maps.Map(document.getElementById('service-map'), {
                zoom: 12,
                center: { lat: this.selectedProperty.latitude, lng: this.selectedProperty.longitude }
            });
            
            // Add property marker
            new google.maps.Marker({
                position: { lat: this.selectedProperty.latitude, lng: this.selectedProperty.longitude },
                map: this.map,
                title: this.selectedProperty.name,
                icon: {
                    url: '/images/property-marker.png',
                    scaledSize: new google.maps.Size(40, 40)
                }
            });
        },
        
        async loadMatchedVendors() {
            try {
                const response = await fetch('/api/property-services/services/match-vendors', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        property_id: this.selectedProperty.id,
                        service_type: this.serviceType,
                        priority: 'standard'
                    })
                });
                
                const data = await response.json();
                this.matchedVendors = data.data;
                
                // Add vendor markers to map
                this.addVendorMarkers();
                
            } catch (error) {
                console.error('Error loading vendors:', error);
            }
        },
        
        addVendorMarkers() {
            this.matchedVendors.forEach(vendor => {
                new google.maps.Marker({
                    position: { 
                        lat: vendor.location.latitude, 
                        lng: vendor.location.longitude 
                    },
                    map: this.map,
                    title: vendor.vendor.shop_title,
                    icon: {
                        url: '/images/vendor-marker.png',
                        scaledSize: new google.maps.Size(30, 30)
                    }
                });
            });
        },
        
        selectVendor(vendor) {
            // Handle vendor selection
            this.$emit('vendor-selected', vendor);
        }
    }
};
</script>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h3 class="stats-number">{{ $stats['pending_requests'] }}</h3>
                            <p class="stats-label">Pending Requests</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h3 class="stats-number">{{ $stats['available_vendors'] }}</h3>
                            <p class="stats-label">Available Vendors</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h3 class="stats-number">${{ number_format($monthlyRevenue, 2) }}</h3>
                            <p class="stats-label">Monthly Revenue</p>
                        </div>
                    </div>