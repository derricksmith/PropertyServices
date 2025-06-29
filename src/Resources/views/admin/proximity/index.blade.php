@extends('admin::layouts.content')

@section('page_title')
    Proximity Multipliers
@stop

@section('content')
    <div class="content">
        <div class="page-header">
            <div class="page-title">
                <h1>Proximity Multipliers</h1>
                <p class="text-muted">Configure distance-based pricing adjustments for service requests</p>
            </div>
        </div>

        <div class="page-content">
            <!-- Analytics Overview -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <h3 class="stats-number">{{ $analytics['total_requests'] }}</h3>
                            <p class="stats-label">Requests (30 days)</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <h3 class="stats-number">{{ $analytics['average_distance'] ?? 0 }} km</h3>
                            <p class="stats-label">Average Distance</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <h3 class="stats-number">{{ $analytics['max_distance'] ?? 0 }} km</h3>
                            <p class="stats-label">Max Distance</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body text-center">
                            <h3 class="stats-number">{{ $analytics['average_proximity_multiplier'] ?? 1 }}x</h3>
                            <p class="stats-label">Avg Multiplier</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Distance Distribution Chart -->
            @if(!empty($analytics['distance_distribution']))
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Distance Distribution (Last 30 Days)</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="distanceChart" width="400" height="100"></canvas>
                    </div>
                </div>
            @endif

            <!-- Configuration Form -->
            <form action="{{ route('property_services.admin.proximity.update') }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Basic Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Basic Settings</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-check-label">
                                        <input type="checkbox" name="enabled" value="1" class="form-check-input" {{ $config['enabled'] ? 'checked' : '' }}>
                                        Enable Proximity Multipliers
                                    </label>
                                    <small class="form-text text-muted">Apply distance-based pricing adjustments</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="base_radius_km">Base Radius (km)</label>
                                    <input type="number" step="0.1" name="base_radius_km" id="base_radius_km" 
                                           class="form-control" value="{{ $config['base_radius_km'] }}" required>
                                    <small class="form-text text-muted">No multiplier within this radius</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="max_radius_km">Maximum Radius (km)</label>
                                    <input type="number" step="0.1" name="max_radius_km" id="max_radius_km" 
                                           class="form-control" value="{{ $config['max_radius_km'] }}" required>
                                    <small class="form-text text-muted">Maximum service distance</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Distance Tiers -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Distance Tiers</h4>
                        <p class="text-muted">Configure pricing multipliers for different distance ranges</p>
                    </div>
                    <div class="card-body">
                        <div id="distance-tiers">
                            @foreach($config['distance_tiers'] as $index => $tier)
                                <div class="tier-row row mb-3" data-index="{{ $index }}">
                                    <div class="col-md-2">
                                        <label>Min Distance (km)</label>
                                        <input type="number" step="0.1" name="distance_tiers[{{ $index }}][min]" 
                                               class="form-control" value="{{ $tier['min'] }}" required>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label>Max Distance (km)</label>
                                        <input type="number" step="0.1" name="distance_tiers[{{ $index }}][max]" 
                                               class="form-control" value="{{ $tier['max'] }}" required>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label>Multiplier</label>
                                        <input type="number" step="0.01" name="distance_tiers[{{ $index }}][multiplier]" 
                                               class="form-control" value="{{ $tier['multiplier'] }}" required>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <label>Label</label>
                                        <input type="text" name="distance_tiers[{{ $index }}][label]" 
                                               class="form-control" value="{{ $tier['label'] }}" required>
                                    </div>
                                    
                                    <div class="col-md-2">
                                        <label>&nbsp;</label>
                                        <div>
                                            <button type="button" class="btn btn-sm btn-danger remove-tier">Remove</button>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-1">
                                        <label>&nbsp;</label>
                                        <div class="tier-preview">
                                            +{{ round(($tier['multiplier'] - 1) * 100, 1) }}%
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <button type="button" id="add-tier" class="btn btn-sm btn-success">Add Tier</button>
                    </div>
                </div>

                <!-- Time-Based Adjustments -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Time-Based Adjustments</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h5>Peak Hours</h5>
                                <div class="form-group">
                                    <label>Additional Multiplier</label>
                                    <input type="number" step="0.01" name="time_based_adjustments[peak_hours][additional_multiplier]" 
                                           class="form-control" value="{{ $config['time_based_adjustments']['peak_hours']['additional_multiplier'] }}">
                                </div>
                                <div class="form-group">
                                    <label>Hours (comma separated)</label>
                                    <input type="text" name="time_based_adjustments[peak_hours][hours]" 
                                           class="form-control" value="{{ implode(',', $config['time_based_adjustments']['peak_hours']['hours']) }}">
                                    <small class="form-text text-muted">Format: 08:00-10:00,17:00-19:00</small>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <h5>Off-Peak Hours</h5>
                                <div class="form-group">
                                    <label>Additional Multiplier</label>
                                    <input type="number" step="0.01" name="time_based_adjustments[off_peak][additional_multiplier]" 
                                           class="form-control" value="{{ $config['time_based_adjustments']['off_peak']['additional_multiplier'] }}">
                                </div>
                                <div class="form-group">
                                    <label>Hours (comma separated)</label>
                                    <input type="text" name="time_based_adjustments[off_peak][hours]" 
                                           class="form-control" value="{{ implode(',', $config['time_based_adjustments']['off_peak']['hours']) }}">
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <h5>Weekend Premium</h5>
                                <div class="form-group">
                                    <label>Additional Multiplier</label>
                                    <input type="number" step="0.01" name="time_based_adjustments[weekend][additional_multiplier]" 
                                           class="form-control" value="{{ $config['time_based_adjustments']['weekend']['additional_multiplier'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Proximity Calculator Test -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h4>Proximity Calculator Test</h4>
                        <p class="text-muted">Test proximity calculations with different scenarios</p>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <input type="number" step="any" id="test_property_lat" class="form-control" placeholder="Property Latitude">
                            </div>
                            <div class="col-md-3">
                                <input type="number" step="any" id="test_property_lng" class="form-control" placeholder="Property Longitude">
                            </div>
                            <div class="col-md-3">
                                <input type="number" step="any" id="test_vendor_lat" class="form-control" placeholder="Vendor Latitude">
                            </div>
                            <div class="col-md-3">
                                <input type="number" step="any" id="test_vendor_lng" class="form-control" placeholder="Vendor Longitude">
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-md-3">
                                <select id="test_service_type" class="form-control">
                                    <option value="cleaning">Cleaning</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="landscaping">Landscaping</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="datetime-local" id="test_datetime" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <select id="test_priority" class="form-control">
                                    <option value="standard">Standard</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="test_market" class="form-control">
                                    @foreach($markets as $market)
                                        <option value="{{ $market->id }}">{{ $market->city_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="button" id="test-calculation" class="btn btn-info">Test Calculation</button>
                            <div id="test-results" class="mt-3" style="display: none;">
                                <div class="alert alert-info">
                                    <h5>Test Results</h5>
                                    <div id="test-output"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Update Settings</button>
                    <a href="{{ route('property_services.admin.dashboard.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <style>
        .stats-card {
            text-align: center;
            padding: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
            margin: 0;
        }
        
        .stats-label {
            color: #6c757d;
            margin: 0;
        }
        
        .tier-row {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            border: 1px solid #dee2e6;
        }
        
        .tier-preview {
            font-weight: bold;
            color: #28a745;
            padding-top: 2rem;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Distance distribution chart
        @if(!empty($analytics['distance_distribution']))
        const ctx = document.getElementById('distanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_keys($analytics['distance_distribution'])) !!},
                datasets: [{
                    label: 'Number of Requests',
                    data: {!! json_encode(array_values($analytics['distance_distribution'])) !!},
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        @endif

        // Add/Remove tier functionality
        let tierIndex = {{ count($config['distance_tiers']) }};

        document.getElementById('add-tier').addEventListener('click', function() {
            const container = document.getElementById('distance-tiers');
            const newTier = document.createElement('div');
            newTier.className = 'tier-row row mb-3';
            newTier.setAttribute('data-index', tierIndex);
            
            newTier.innerHTML = `
                <div class="col-md-2">
                    <label>Min Distance (km)</label>
                    <input type="number" step="0.1" name="distance_tiers[${tierIndex}][min]" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label>Max Distance (km)</label>
                    <input type="number" step="0.1" name="distance_tiers[${tierIndex}][max]" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label>Multiplier</label>
                    <input type="number" step="0.01" name="distance_tiers[${tierIndex}][multiplier]" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label>Label</label>
                    <input type="text" name="distance_tiers[${tierIndex}][label]" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label>&nbsp;</label>
                    <div>
                        <button type="button" class="btn btn-sm btn-danger remove-tier">Remove</button>
                    </div>
                </div>
                <div class="col-md-1">
                    <label>&nbsp;</label>
                    <div class="tier-preview"></div>
                </div>
            `;
            
            container.appendChild(newTier);
            tierIndex++;
        });

        // Remove tier functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-tier')) {
                e.target.closest('.tier-row').remove();
            }
        });

        // Test calculation functionality
        document.getElementById('test-calculation').addEventListener('click', async function() {
            const data = {
                property_lat: document.getElementById('test_property_lat').value,
                property_lng: document.getElementById('test_property_lng').value,
                vendor_lat: document.getElementById('test_vendor_lat').value,
                vendor_lng: document.getElementById('test_vendor_lng').value,
                service_type: document.getElementById('test_service_type').value,
                requested_datetime: document.getElementById('test_datetime').value,
                priority: document.getElementById('test_priority').value,
                market_id: document.getElementById('test_market').value
            };

            try {
                const response = await fetch('{{ route("property_services.admin.proximity.test") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                
                if (result.success) {
                    const output = document.getElementById('test-output');
                    output.innerHTML = `
                        <p><strong>Distance:</strong> ${result.data.distance_km.toFixed(2)} km</p>
                        <p><strong>Tier:</strong> ${result.data.tier}</p>
                        <p><strong>Total Multiplier:</strong> ${result.data.multiplier}x</p>
                        <p><strong>Price Increase:</strong> ${result.data.total_increase_percentage}%</p>
                        <p><strong>Explanation:</strong> ${result.explanation}</p>
                        <details>
                            <summary>Breakdown</summary>
                            <pre>${JSON.stringify(result.data.breakdown, null, 2)}</pre>
                        </details>
                    `;
                    document.getElementById('test-results').style.display = 'block';
                } else {
                    alert('Test failed: ' + result.message);
                }
            } catch (error) {
                alert('Test failed: ' + error.message);
            }
        });

        // Set default test datetime to now + 2 hours
        document.getElementById('test_datetime').value = new Date(Date.now() + 2 * 60 * 60 * 1000).toISOString().slice(0, 16);
    </script>
@stop
    