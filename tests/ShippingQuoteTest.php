<?php

namespace ItmoSoft\Shipping\Tests;

use InvalidArgumentException;
use ItmoSoft\Shipping\Models\ShippingMethod;
use ItmoSoft\Shipping\Models\ShippingZone;
use ItmoSoft\Shipping\Services\ShippingQuoteService;

class ShippingQuoteTest extends TestCase
{
    public function test_flat_rate_quote_is_returned_for_matching_country(): void
    {
        $method = ShippingMethod::create(['name' => 'Standard', 'code' => 'standard', 'is_active' => true]);
        $zone = ShippingZone::create(['name' => 'Pakistan', 'is_active' => true]);
        $zone->rules()->create(['type' => 'country', 'value' => 'PK']);
        $method->rates()->create(['zone_id' => $zone->id, 'type' => 'flat', 'amount' => 500, 'currency' => 'PKR']);

        $quotes = app(ShippingQuoteService::class)->quote(
            ['country' => 'PK'],
            ['subtotal' => 1000, 'weight' => 1, 'currency' => 'PKR']
        );

        $this->assertCount(1, $quotes);
        $this->assertSame(500.0, $quotes[0]['amount']);
    }

    public function test_non_matching_destination_returns_no_quotes(): void
    {
        $method = ShippingMethod::create(['name' => 'Standard', 'code' => 'standard', 'is_active' => true]);
        $zone = ShippingZone::create(['name' => 'Pakistan', 'is_active' => true]);
        $zone->rules()->create(['type' => 'country', 'value' => 'PK']);
        $method->rates()->create(['zone_id' => $zone->id, 'type' => 'flat', 'amount' => 500, 'currency' => 'PKR']);

        $this->assertSame([], app(ShippingQuoteService::class)->quote(['country' => 'US'], ['currency' => 'PKR']));
    }

    public function test_different_rule_types_are_cumulative_constraints(): void
    {
        $method = ShippingMethod::create(['name' => 'Regional', 'code' => 'regional', 'is_active' => true]);
        $zone = ShippingZone::create(['name' => 'Punjab', 'is_active' => true]);
        $zone->rules()->createMany([
            ['type' => 'country', 'value' => 'PK'],
            ['type' => 'region', 'value' => 'Punjab'],
        ]);
        $method->rates()->create(['zone_id' => $zone->id, 'type' => 'flat', 'amount' => 300, 'currency' => 'PKR']);

        $service = app(ShippingQuoteService::class);

        $this->assertCount(1, $service->quote(
            ['country' => 'PK', 'region' => 'Punjab'],
            ['subtotal' => 1000, 'currency' => 'PKR']
        ));
        $this->assertSame([], $service->quote(
            ['country' => 'PK', 'region' => 'Sindh'],
            ['subtotal' => 1000, 'currency' => 'PKR']
        ));
    }

    public function test_flat_rate_minimum_and_maximum_use_order_subtotal(): void
    {
        $method = ShippingMethod::create(['name' => 'Standard', 'code' => 'standard', 'is_active' => true]);
        $zone = ShippingZone::create(['name' => 'Pakistan', 'is_active' => true]);
        $zone->rules()->create(['type' => 'country', 'value' => 'PK']);
        $method->rates()->create([
            'zone_id' => $zone->id, 'type' => 'flat', 'amount' => 500, 'currency' => 'PKR',
            'min_value' => 1000, 'max_value' => 5000,
        ]);

        $service = app(ShippingQuoteService::class);

        $this->assertSame([], $service->quote(['country' => 'PK'], ['subtotal' => 999, 'currency' => 'PKR']));
        $this->assertCount(1, $service->quote(['country' => 'PK'], ['subtotal' => 1000, 'currency' => 'PKR']));
        $this->assertSame([], $service->quote(['country' => 'PK'], ['subtotal' => 5001, 'currency' => 'PKR']));
    }

    public function test_only_lowest_matching_rate_is_returned_per_method(): void
    {
        $method = ShippingMethod::create(['name' => 'Standard', 'code' => 'standard', 'is_active' => true]);
        $zone = ShippingZone::create(['name' => 'Pakistan', 'is_active' => true]);
        $zone->rules()->create(['type' => 'country', 'value' => 'PK']);
        $method->rates()->createMany([
            ['zone_id' => $zone->id, 'type' => 'flat', 'amount' => 500, 'currency' => 'PKR'],
            ['zone_id' => $zone->id, 'type' => 'flat', 'amount' => 300, 'currency' => 'PKR'],
        ]);

        $quotes = app(ShippingQuoteService::class)->quote(['country' => 'PK'], ['currency' => 'PKR']);

        $this->assertCount(1, $quotes);
        $this->assertSame(300.0, $quotes[0]['amount']);
    }

    public function test_non_finite_quote_inputs_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(ShippingQuoteService::class)->quote([], ['subtotal' => INF]);
    }

    public function test_invalid_currency_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(ShippingQuoteService::class)->quote([], ['currency' => 'USDD']);
    }
}
