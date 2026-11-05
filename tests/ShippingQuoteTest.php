<?php
namespace ItmoSoft\Shipping\Tests;
use ItmoSoft\Shipping\Models\ShippingMethod;
use ItmoSoft\Shipping\Models\ShippingZone;
use ItmoSoft\Shipping\Services\ShippingQuoteService;
class ShippingQuoteTest extends TestCase
{
    public function test_flat_rate_quote_is_returned_for_matching_country(): void
    {
        $method = ShippingMethod::create(['name'=>'Standard','code'=>'standard','is_active'=>true]);
        $zone = ShippingZone::create(['name'=>'Pakistan','is_active'=>true]);
        $zone->rules()->create(['type'=>'country','value'=>'PK']);
        $method->rates()->create(['zone_id'=>$zone->id,'type'=>'flat','amount'=>500,'currency'=>'PKR']);
        $quotes = app(ShippingQuoteService::class)->quote(['country'=>'PK'], ['subtotal'=>1000,'weight'=>1,'currency'=>'PKR']);
        $this->assertCount(1, $quotes);
        $this->assertSame(500.0, $quotes[0]['amount']);
    }
    public function test_non_matching_destination_returns_no_quotes(): void
    {
        $method = ShippingMethod::create(['name'=>'Standard','code'=>'standard','is_active'=>true]);
        $zone = ShippingZone::create(['name'=>'Pakistan','is_active'=>true]);
        $zone->rules()->create(['type'=>'country','value'=>'PK']);
        $method->rates()->create(['zone_id'=>$zone->id,'type'=>'flat','amount'=>500,'currency'=>'PKR']);
        $this->assertSame([], app(ShippingQuoteService::class)->quote(['country'=>'US'], ['currency'=>'PKR']));
    }
}
