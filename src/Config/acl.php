<?php

return [
    [
        'key'   => 'property_services',
        'name'  => 'property_services::app.admin.acl.property-services',
        'route' => 'property_services.admin.dashboard.index',
        'sort'  => 6,
    ], [
        'key'   => 'property_services.properties',
        'name'  => 'property_services::app.admin.acl.properties',
        'route' => 'property_services.admin.properties.index',
        'sort'  => 1,
    ], [
        'key'   => 'property_services.service-requests',
        'name'  => 'property_services::app.admin.acl.service-requests',
        'route' => 'property_services.admin.service-requests.index',
        'sort'  => 2,
    ], [
        'key'   => 'property_services.markets',
        'name'  => 'property_services::app.admin.acl.markets',
        'route' => 'property_services.admin.markets.index',
        'sort'  => 3,
    ], [
        'key'   => 'property_services.service-areas',
        'name'  => 'property_services::app.admin.acl.service-areas',
        'route' => 'property_services.admin.service-areas.index',
        'sort'  => 4,
    ]
];
