# PropertyServices - Bagisto Package

An "Uber for Property Services" marketplace package for Laravel Bagisto 2.3-dev.

## Installation

1. Add to your main composer.json:
```json
{
    "repositories": [
        {
            "type": "path",
            "url": "packages/Webkul/PropertyServices"
        }
    ],
    "require": {
        "webkul/property-services": "*"
    }
}
```

2. Install the package:
```bash
composer update webkul/property-services
php artisan property-services:install
```

3. Configure your .env file with Google Maps API key and other settings.

## Features

- Interactive homepage with map
- Property import from VRBO/Owner Reservations
- Proximity-based pricing
- Comprehensive admin dashboard
- Customer property management
- Real-time seller tracking

## Usage

- Admin: `/admin/property-services`
- Customer: `/property-services`

## Support

For support and documentation, visit the admin dashboard.
