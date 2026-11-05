<?php
namespace ItmoSoft\Shipping\Tests;
use ItmoSoft\Shipping\Models\Shipment;
class ShipmentTest extends TestCase
{
    public function test_shipment_can_record_tracking_event(): void
    {
        $shipment = Shipment::create(['order_id'=>42,'status'=>'pending','recipient'=>['name'=>'Test']]);
        $event = $shipment->recordEvent('picked_up','Parcel collected');
        $this->assertSame('picked_up', $shipment->fresh()->status);
        $this->assertSame('picked_up', $event->status);
        $this->assertSame(1, $shipment->events()->count());
    }
}
