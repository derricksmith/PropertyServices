<?php

return [
    [
        'key'        => 'property_services',
        'name'       => 'property_services::app.admin.layouts.property-services',
        'route'      => 'property_services.admin.dashboard.index',
        'sort'       => 6,
        'icon' => 'dashboard-icon',
    ], [
        'key'        => 'property_services.properties',
        'name'       => 'property_services::app.admin.layouts.properties',
        'route'      => 'property_services.admin.properties.index',
        'sort'       => 1,
        'icon' => 'dashboard-icon',
    ], [
        'key'        => 'property_services.service-requests',
        'name'       => 'property_services::app.admin.layouts.service-requests',
        'route'      => 'property_services.admin.service-requests.index',
        'sort'       => 2,
        'icon' => 'dashboard-icon',
    ], [
        'key'        => 'property_services.markets',
        'name'       => 'property_services::app.admin.layouts.markets',
        'route'      => 'property_services.admin.markets.index',
        'sort'       => 3,
        'icon' => 'dashboard-icon',
    ], [
        'key'        => 'property_services.service-areas',
        'name'       => 'property_services::app.admin.layouts.service-areas',
        'route'      => 'property_services.admin.service-areas.index',
        'sort'       => 4,
        'icon' => 'dashboard-icon',
    ]
];
