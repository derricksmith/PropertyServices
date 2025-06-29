@extends('admin::layouts.content')

@section('page_title')
    {{ __('property_services::app.admin.dashboard.title') }}
@stop

@section('content')
    <div class="content">
        <div class="page-header">
            <div class="page-title">
                <h1>{{ __('property_services::app.admin.dashboard.title') }}</h1>
            </div>
        </div>

        <div class="page-content">
            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card stats-card">
                        <div class="card-body">
                            <h3 class="stats-number">{{ $stats['total_properties'] }}</h3>
                            <p class="stats-label">Total Properties</p>
                        </div>
            </div>

            <!-- Recent Service Requests -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Recent Service Requests</h4>
                            <a href="{{ route('property_services.admin.service-requests.index') }}" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        <div class="card-body">
                            @if($recentRequests->count())
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Property</th>
                                                <th>Customer</th>
                                                <th>Service Type</th>
                                                <th>Status</th>
                                                <th>Requested Date</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recentRequests as $request)
                                                <tr>
                                                    <td>#{{ $request->id }}</td>
                                                    <td>{{ $request->property->name }}</td>
                                                    <td>{{ $request->property->customer->first_name }} {{ $request->property->customer->last_name }}</td>
                                                    <td>{{ ucfirst($request->service_type) }}</td>
                                                    <td>
                                                        <span class="badge badge-{{ $request->status === 'completed' ? 'success' : ($request->status === 'pending' ? 'warning' : 'info') }}">
                                                            {{ ucfirst($request->status) }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $request->requested_datetime->format('M d, Y H:i') }}</td>
                                                    <td>${{ number_format($request->estimated_cost, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <p>No service requests found.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop