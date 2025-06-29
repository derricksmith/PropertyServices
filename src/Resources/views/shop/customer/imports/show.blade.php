@extends('shop::customers.account.index')

@section('page_title')
    {{ $connection->provider_name }} Import Details
@stop

@section('account-content')
    <div class="account-content">
        <div class="account-layout">
            <div class="account-head">
                <span class="back-icon">
                    <a href="{{ route('shop.customer.imports.index') }}">
                        <i class="icon icon-menu-back"></i>
                    </a>
                </span>

                <span class="account-heading">
                    {{ $connection->provider_name }} Import Details
                </span>

                <span class="account-action">
                    <div class="action-buttons">
                        @if($connection->is_active)
                            <form action="{{ route('shop.customer.imports.manual-import', $connection) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success" onclick="return confirm('Start manual import now?')">
                                    <i class="icon icon-refresh"></i> Import Now
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('shop.customer.imports.edit', $connection) }}" class="btn btn-primary">
                            <i class="icon icon-edit"></i> Edit Settings
                        </a>
                    </div>
                </span>
            </div>

            <!-- Connection Status Card -->
            <div class="info-card status-card">
                <h3>Connection Status</h3>
                <div class="status-grid">
                    <div class="status-item">
                        <span class="label">Status:</span>
                        <span class="status-badge badge-{{ $connection->is_active ? 'success' : 'danger' }}">
                            {{ $connection->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="status-item">
                        <span class="label">Auto Sync:</span>
                        <span class="value">{{ $connection->auto_sync_enabled ? 'Enabled' : 'Disabled' }}</span>
                    </div>

                    <div class="status-item">
                        <span class="label">Frequency:</span>
                        <span class="value">{{ ucfirst($connection->sync_frequency) }}</span>
                    </div>

                    @if($connection->last_sync_at)
                        <div class="status-item">
                            <span class="label">Last Sync:</span>
                            <span class="value">{{ $connection->last_sync_at->format('M j, Y g:i A') }}</span>
                        </div>

                        <div class="status-item">
                            <span class="label">Last Result:</span>
                            <span class="status-badge badge-{{ $connection->last_sync_status === 'success' ? 'success' : ($connection->last_sync_status === 'failed' ? 'danger' : 'warning') }}">
                                {{ ucfirst($connection->last_sync_status) }}
                            </span>
                        </div>
                    @endif

                    @if($connection->getNextSyncTime())
                        <div class="status-item">
                            <span class="label">Next Sync:</span>
                            <span class="value">{{ $connection->getNextSyncTime()->format('M j, Y g:i A') }}</span>
                        </div>
                    @endif
                </div>

                @if($connection->sync_errors)
                    <div class="error-section">
                        <h4>Recent Errors:</h4>
                        <ul class="error-list">
                            @foreach($connection->sync_errors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <!-- Import Statistics -->
            <div class="info-card stats-card">
                <h3>Import Statistics</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">{{ $connection->importedProperties->count() }}</div>
                        <div class="stat-label">Total Properties</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-number">{{ $connection->importedProperties->where('import_status', 'imported')->count() }}</div>
                        <div class="stat-label">Successfully Imported</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-number">{{ $connection->importedProperties->where('import_status', 'updated')->count() }}</div>
                        <div class="stat-label">Updated</div>
                    </div>

                    <div class="stat-item">
                        <div class="stat-number">{{ $connection->importedProperties->where('import_status', 'failed')->count() }}</div>
                        <div class="stat-label">Failed</div>
                    </div>
                </div>
            </div>

            <!-- Recent Imports -->
            @if($recentImports->count())
                <div class="info-card imports-card">
                    <h3>Recent Imports</h3>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Property Name</th>
                                    <th>Status</th>
                                    <th>Imported Date</th>
                                    <th>External ID</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentImports as $import)
                                    <tr>
                                        <td>
                                            @if($import->property)
                                                <a href="{{ route('shop.customer.properties.show', $import->property) }}">
                                                    {{ $import->property->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">Property Deleted</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="status-badge badge-{{ $import->import_status === 'imported' ? 'success' : ($import->import_status === 'failed' ? 'danger' : 'info') }}">
                                                {{ ucfirst($import->import_status) }}
                                            </span>
                                        </td>
                                        <td>{{ $import->last_imported_at?->format('M j, Y g:i A') ?? 'Never' }}</td>
                                        <td><code>{{ $import->external_id }}</code></td>
                                        <td>
                                            @if($import->property)
                                                <a href="{{ route('shop.customer.properties.show', $import->property) }}" class="btn btn-sm btn-outline-primary">
                                                    View Property
                                                </a>
                                            @endif
                                            
                                            <form action="{{ route('shop.customer.imports.remove-imported', $import) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this imported property?')">
                                                    Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($connection->importedProperties->count() > 10)
                        <div class="view-all-link">
                            <a href="{{ route('shop.customer.imports.history') }}" class="btn btn-link">
                                View All Imported Properties
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <div class="info-card empty-card">
                    <div class="empty-state">
                        <i class="icon icon-package" style="font-size: 3rem; color: #ccc;"></i>
                        <h4>No Properties Imported Yet</h4>
                        <p>No properties have been imported from this connection yet.</p>
                        @if($connection->is_active)
                            <form action="{{ route('shop.customer.imports.manual-import', $connection) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-primary">Import Properties Now</button>
                            </form>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    <style>
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .info-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-card h3 {
            margin: 0 0 1rem 0;
            color: #333;
            font-size: 1.25rem;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f8f9fa;
        }

        .status-item:last-child {
            border-bottom: none;
        }

        .status-item .label {
            font-weight: 600;
            color: #495057;
        }

        .status-item .value {
            color: #212529;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #007bff;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .error-section {
            margin-top: 1rem;
            padding: 1rem;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 0.25rem;
        }

        .error-section h4 {
            margin: 0 0 0.5rem 0;
            color: #721c24;
        }

        .error-list {
            margin: 0;
            padding-left: 1rem;
            color: #721c24;
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
        }

        .empty-state h4 {
            margin: 1rem 0 0.5rem 0;
            color: #6c757d;
        }

        .empty-state p {
            color: #6c757d;
            margin-bottom: 1rem;
        }

        .view-all-link {
            text-align: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }

        .table {
            margin: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }

        .table td {
            vertical-align: middle;
        }
    </style>
@stop