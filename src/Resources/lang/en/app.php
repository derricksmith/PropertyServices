<?php

return [
    'admin' => [
        'layouts' => [
            'property-services' => 'Property Services',
            'properties' => 'Properties',
            'service-requests' => 'Service Requests',
            'markets' => 'Markets',
            'service-areas' => 'Service Areas',
            'dashboard' => 'Dashboard'
        ],
        
        'dashboard' => [
            'title' => 'Property Services Dashboard',
            'total-properties' => 'Total Properties',
            'pending-requests' => 'Pending Requests',
            'available-vendors' => 'Available Vendors',
            'monthly-revenue' => 'Monthly Revenue'
        ],
        
        'properties' => [
            'title' => 'Properties',
            'create-title' => 'Add New Property',
            'edit-title' => 'Edit Property',
            'create-success' => 'Property created successfully.',
            'update-success' => 'Property updated successfully.',
            'delete-success' => 'Property deleted successfully.',
            'delete-error-active-requests' => 'Cannot delete property with active service requests.'
        ],
        
        'service-requests' => [
            'title' => 'Service Requests',
            'assign-vendor' => 'Assign Vendor',
            'update-status' => 'Update Status'
        ],
        
        'markets' => [
            'title' => 'Markets',
            'create-title' => 'Add New Market',
            'edit-title' => 'Edit Market'
        ],
        
        'acl' => [
            'property-services' => 'Property Services',
            'properties' => 'Properties',
            'service-requests' => 'Service Requests',
            'markets' => 'Markets',
            'service-areas' => 'Service Areas'
        ]
    ],
    
    'shop' => [
        'customer' => [
            'properties' => [
                'title' => 'My Properties',
                'add-property' => 'Add Property',
                'no-properties' => 'No properties found.',
                'property-details' => 'Property Details',
                'request-service' => 'Request Service'
            ],
            
            'services' => [
                'title' => 'Service History',
                'request-service' => 'Request New Service',
                'service-types' => [
                    'cleaning' => 'Cleaning',
                    'maintenance' => 'Maintenance',
                    'landscaping' => 'Landscaping',
                    'pest-control' => 'Pest Control',
                    'plumbing' => 'Plumbing',
                    'electrical' => 'Electrical'
                ]
            ]
        ]
    ]
];
