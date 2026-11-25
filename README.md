# ITMOSOFT Laravel Shipping

Shipping zones, methods, rates, quotes, shipments and tracking for Laravel 12 and 13.

## Installation
```bash
composer require itmosoft/laravel-shipping
php artisan vendor:publish --tag=itmosoft-shipping-config
php artisan vendor:publish --tag=itmosoft-shipping-migrations
php artisan migrate
```

## Configuration
Set `table_prefix` in `config/itmosoft-shipping.php` if you need prefixed tables. Default is `shipping_`. The `statuses` array defines valid shipment statuses. Currency values must be three-letter codes.

## Create a method, zone and rate
```php
use ItmoSoft\Shipping\Models\ShippingMethod;
use ItmoSoft\Shipping\Models\ShippingZone;

$method = ShippingMethod::create(['name' => 'Standard Delivery', 'code' => 'standard', 'is_active' => true]);
$zone = ShippingZone::create(['name' => 'Pakistan', 'is_active' => true]);
$zone->rules()->create(['type' => 'country', 'value' => 'PK']);
$method->rates()->create(['zone_id' => $zone->id, 'type' => 'flat', 'amount' => 500, 'currency' => 'PKR']);
```

## Get a quote
```php
use ItmoSoft\Shipping\Services\ShippingQuoteService;

$quote = app(ShippingQuoteService::class)->quote([
    'country' => 'PK', 'region' => 'Punjab', 'postal_code' => '60000',
], ['subtotal' => 10000, 'weight' => 2.5, 'currency' => 'PKR']);
```

Rate types:
- `flat` uses `amount` as a fixed shipping amount.
- `weight` uses `amount` per weight unit.
- `subtotal` treats `amount` as a percentage of order subtotal.

`min_value` and `max_value` filter on order subtotal for flat/subtotal rates and weight for weight rates. A quote returns at most one option per shipping method: the lowest eligible rate in the requested currency. Currency values are normalized to uppercase and must contain exactly three letters. Quote inputs and rate amounts must be finite, non-negative numeric values.

Within a zone, multiple rules of the same type are alternatives; different rule types are cumulative constraints. For example, a country rule and a region rule must both match.

## Create a shipment
```php
use ItmoSoft\Shipping\Models\Shipment;

$shipment = Shipment::create([
    'order_id' => $order->id, 'shipping_method_id' => $method->id,
    'status' => 'pending', 'recipient' => ['name' => 'Customer', 'address' => 'Street'],
    'tracking_number' => 'TRACK-001',
]);
$shipment->recordEvent('picked_up', 'Parcel collected');
```

`order_id` is intentionally a scalar reference, not a foreign key, so the package does not require a specific Orders package/model. `recipient` is a required JSON snapshot. Tracking events are recorded transactionally and statuses must exist in the configured `statuses` list.

## Events
- `ItmoSoft\Shipping\Events\ShipmentCreated`
- `ItmoSoft\Shipping\Events\ShipmentStatusChanged`

## Tests
```bash
composer install
composer test
```

## Boundaries
This package calculates configured rates and records shipments. It does not contact carriers, generate labels, guarantee delivery dates, calculate tax, or automatically reserve inventory. Validate shipping rules and prices for your business and jurisdictions.

## License
MIT
