@extends('shop::customers.account.index')

@section('page_title')
    Property Import Settings
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
                    Property Import Settings
                </span>

                <span class="account-action">
                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                            Connect New Provider
                        </button>
                        <div class="dropdown-menu">
                            @foreach($availableProviders as $key => $name)
                                <a class="dropdown-item" href="{{ route('shop.customer.imports.create', ['provider' => $key]) }}">
                                    {{ $name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </span>
            </div>

            @if($connections->count())
                <div class="account-items-list">
                    @foreach($connections as $connection)
                        <div class="account-item-card import-connection-card">
                            <div class="connection-info">
                                <div class="provider-info">
                                    <h4>{{ $connection->provider_name }}</h4>
                                    <div class="connection-status">
                                        <span class="status-badge badge-{{ $connection->is_active ? 'success' : 'danger' }}">
                                            {{ $connection->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        @if($connection->auto_sync_enabled)
                                            <span class="sync-badge badge-info">
                                                Auto-sync: {{ ucfirst($connection->sync_frequency) }}
                                            </span>
                                        @else
                                            <span class="sync-badge badge-secondary">
                                                Manual sync only
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="sync-info">
                                    @if($connection->last_sync_at)
                                        <div class="last-sync">
                                            <strong>Last Sync:</strong> {{ $connection->last_sync_at->diffForHumans() }}
                                            <span class="sync-status badge-{{ $connection->last_sync_status === 'success' ? 'success' : ($connection->last_sync_status === 'failed' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($connection->last_sync_status) }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="last-sync">
                                            <em>Never synced</em>
                                        </div>
                                    @endif

                                    @if($connection->getNextSyncTime())
                                        <div class="next-sync">
                                            <strong>Next Sync:</strong> {{ $connection->getNextSyncTime()->diffForHumans() }}
                                        </div>
                                    @endif
                                </div>

                                <div class="import-stats">
                                    <span class="stat-item">
                                        <strong>{{ $connection->imported_properties_count }}</strong> Properties Imported
                                    </span>
                                    @if($connection->sync_errors)
                                        <span class="error-indicator" title="{{ implode(', ', $connection->sync_errors) }}">
                                            <i class="icon icon-warning"></i> {{ count($connection->sync_errors) }} Error(s)
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="connection-actions">
                                <a href="{{ route('shop.customer.imports.show', $connection) }}" class="btn btn-sm btn-primary">
                                    View Details
                                </a>

                                @if($connection->is_active)
                                    <form action="{{ route('shop.customer.imports.manual-import', $connection) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Start manual import now?')">
                                            Import Now
                                        </button>
                                    </form>
                                @endif

                                <div class="dropdown">
                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" data-toggle="dropdown">
                                        More
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="{{ route('shop.customer.imports.edit', $connection) }}">
                                            Edit Settings
                                        </a>
                                        
                                        <form action="{{ route('shop.customer.imports.test', $connection) }}" method="POST" class="dropdown-form">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Test Connection</button>
                                        </form>

                                        <form action="{{ route('shop.customer.imports.toggle', $connection) }}" method="POST" class="dropdown-form">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="dropdown-item">
                                                {{ $connection->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <div class="dropdown-divider"></div>
                                        
                                        <form action="{{ route('shop.customer.imports.destroy', $connection) }}" method="POST" class="dropdown-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Are you sure? This will remove the connection but keep imported properties.')">
                                                Delete Connection
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="import-history-link">
                    <a href="{{ route('shop.customer.imports.history') }}" class="btn btn-link">
                        View Complete Import History
                    </a>
                </div>
            @else
                <div class="empty-imports">
                    <div class="empty-info">
                        <i class="icon icon-cloud-upload" style="font-size: 4rem; color: #ccc;"></i>
                        <h3>No Import Connections</h3>
                        <p>Connect to your property management platforms to automatically import your properties and keep them in sync.</p>
                        
                        <div class="provider-options">
                            @foreach($availableProviders as $key => $name)
                                <a href="{{ route('shop.customer.imports.create', ['provider' => $key]) }}" class="btn btn-primary provider-btn">
                                    Connect {{ $name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <style>
        .import-connection-card {
            border-left: 4px solid #007bff;
            margin-bottom: 1rem;
        }

        .connection-info {
            flex-grow: 1;
        }

        .provider-info h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
        }

        .connection-status {
            margin-bottom: 1rem;
        }

        .status-badge, .sync-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            margin-right: 0.5rem;
        }

        .badge-success { background-color: #28a745; color: white; }
        .badge-danger { background-color: #dc3545; color: white; }
        .badge-info { background-color: #17a2b8; color: white; }
        .badge-warning { background-color: #ffc107; color: #212529; }
        .badge-secondary { background-color: #6c757d; color: white; }

        .sync-info {
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        .import-stats {
            font-size: 0.9rem;
        }

        .stat-item {
            margin-right: 1rem;
        }

        .error-indicator {
            color: #dc3545;
            cursor: help;
        }

        .connection-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 200px;
        }

        .dropdown-form {
            margin: 0;
        }

        .dropdown-form button {
            background: none;
            border: none;
            width: 100%;
            text-align: left;
            padding: 0.375rem 1.5rem;
            color: #212529;
        }

        .dropdown-form button:hover {
            background-color: #f8f9fa;
        }

        .empty-imports {
            text-align: center;
            padding: 3rem 1rem;
        }

        .provider-options {
            margin-top: 2rem;
        }

        .provider-btn {
            margin: 0.5rem;
        }

        .import-history-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #dee2e6;
        }
    </style>
@stop