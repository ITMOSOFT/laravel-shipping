<?php

return [
    'table_prefix' => env('ITMOSOFT_SHIPPING_TABLE_PREFIX', 'shipping_'),
    'currency' => env('ITMOSOFT_SHIPPING_CURRENCY', 'USD'),
    'statuses' => ['pending', 'processing', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed', 'returned', 'cancelled'],
];
