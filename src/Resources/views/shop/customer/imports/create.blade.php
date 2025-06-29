@extends('shop::customers.account.index')

@section('page_title')
    Connect {{ ucfirst(str_replace('_', ' ', $provider)) }}
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
                    Connect {{ ucfirst(str_replace('_', ' ', $provider)) }}
                </span>
            </div>

            <form action="{{ route('shop.customer.imports.store') }}" method="POST" class="account-form">
                @csrf
                <input type="hidden" name="provider" value="{{ $provider }}">

                <div class="form-section">
                    <h3>API Credentials</h3>
                    <p class="text-muted">Enter your {{ ucfirst(str_replace('_', ' ', $provider)) }} API credentials to connect your account.</p>

                    @foreach($requiredCredentials as $field => $config)
                        <div class="control-group">
                            <label for="{{ $field }}" class="{{ $config['required'] ? 'required' : '' }}">
                                {{ $config['label'] }}
                            </label>
                            
                            @if($config['type'] === 'password')
                                <input type="password" 
                                       name="credentials[{{ $field }}]" 
                                       id="{{ $field }}" 
                                       class="control" 
                                       {{ $config['required'] ? 'required' : '' }}
                                       value="{{ old("credentials.{$field}") }}">
                            @else
                                <input type="text" 
                                       name="credentials[{{ $field }}]" 
                                       id="{{ $field }}" 
                                       class="control" 
                                       {{ $config['required'] ? 'required' : '' }}
                                       value="{{ old("credentials.{$field}") }}">
                            @endif

                            @if(!empty($config['help']))
                                <small class="help-text">{{ $config['help'] }}</small>
                            @endif

                            @error("credentials.{$field}")
                                <span class="control-error">{{ $message }}</span>
                            @enderror
                        </div>
                    @endforeach
                </div>

                <div class="form-section">
                    <h3>Sync Settings</h3>

                    <div class="control-group">
                        <label for="sync_frequency" class="required">Sync Frequency</label>
                        <select name="sync_frequency" id="sync_frequency" class="control" required>
                            @foreach(\Webkul\PropertyServices\Models\PropertyImportConnection::SYNC_FREQUENCIES as $key => $label)
                                <option value="{{ $key }}" {{ old('sync_frequency', 'daily') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <small class="help-text">How often should we automatically sync your properties?</small>
                        @error('sync_frequency')
                            <span class="control-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="control-group">
                        <label class="checkbox-container">
                            <input type="hidden" name="auto_sync_enabled" value="0">
                            <input type="checkbox" name="auto_sync_enabled" value="1" {{ old('auto_sync_enabled', true) ? 'checked' : '' }}>
                            <span class="checkmark"></span>
                            Enable automatic syncing
                        </label>
                        <small class="help-text">Uncheck to only sync manually</small>
                    </div>
                </div>

                @if(!empty($availableSettings))
                    <div class="form-section">
                        <h3>Import Settings</h3>

                        @foreach($availableSettings as $setting => $config)
                            <div class="control-group">
                                @if($config['type'] === 'boolean')
                                    <label class="checkbox-container">
                                        <input type="hidden" name="settings[{{ $setting }}]" value="0">
                                        <input type="checkbox" 
                                               name="settings[{{ $setting }}]" 
                                               value="1" 
                                               {{ old("settings.{$setting}", $config['default'] ?? false) ? 'checked' : '' }}>
                                        <span class="checkmark"></span>
                                        {{ $config['label'] }}
                                    </label>
                                @else
                                    <label for="setting_{{ $setting }}">{{ $config['label'] }}</label>
                                    <input type="{{ $config['type'] }}" 
                                           name="settings[{{ $setting }}]" 
                                           id="setting_{{ $setting }}" 
                                           class="control"
                                           value="{{ old("settings.{$setting}", $config['default'] ?? '') }}">
                                @endif

                                @if(!empty($config['help']))
                                    <small class="help-text">{{ $config['help'] }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="button-group">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Connect and Test
                    </button>
                    
                    <a href="{{ route('shop.customer.imports.index') }}" class="btn btn-secondary btn-lg">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <style>
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .form-section:last-of-type {
            border-bottom: none;
        }

        .form-section h3 {
            margin-bottom: 1rem;
            color: #333;
            font-size: 1.25rem;
        }

        .help-text {
            display: block;
            margin-top: 0.25rem;
            color: #6c757d;
            font-size: 0.875rem;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            cursor: pointer;
            position: relative;
            padding-left: 1.5rem;
        }

        .checkbox-container input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .checkmark {
            position: absolute;
            left: 0;
            height: 1rem;
            width: 1rem;
            background-color: #fff;
            border: 2px solid #ddd;
            border-radius: 0.25rem;
        }

        .checkbox-container input:checked ~ .checkmark {
            background-color: #007bff;
            border-color: #007bff;
        }

        .checkmark:after {
            content: "";
            position: absolute;
            display: none;
        }

        .checkbox-container input:checked ~ .checkmark:after {
            display: block;
        }

        .checkbox-container .checkmark:after {
            left: 0.25rem;
            top: 0.125rem;
            width: 0.25rem;
            height: 0.5rem;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
    </style>
@stop
