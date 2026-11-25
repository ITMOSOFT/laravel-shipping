<?php

namespace ItmoSoft\Shipping\Tests;

use InvalidArgumentException;
use ItmoSoft\Shipping\Models\Shipment;

class ShipmentTest extends TestCase
{
    public function test_shipment_can_record_tracking_event(): void
    {
        $shipment = Shipment::create([
            'order_id' => 42,
            'status' => 'pending',
            'recipient' => ['name' => 'Test'],
        ]);

        $event = $shipment->recordEvent('picked_up', 'Parcel collected');

        $this->assertSame('picked_up', $shipment->fresh()->status);
        $this->assertSame('picked_up', $event->status);
        $this->assertSame(1, $shipment->events()->count());
    }

    public function test_invalid_shipment_status_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Shipment::create([
            'order_id' => 42,
            'status' => 'teleported',
            'recipient' => ['name' => 'Test'],
        ]);
    }

    public function test_tracking_event_requires_a_persisted_shipment(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Shipment())->recordEvent('picked_up');
    }

    public function test_tracking_event_rejects_unknown_status(): void
    {
        $shipment = Shipment::create([
            'order_id' => 42,
            'status' => 'pending',
            'recipient' => ['name' => 'Test'],
        ]);

        $this->expectException(InvalidArgumentException::class);

        $shipment->recordEvent('teleported');
    }

    public function test_shipment_requires_recipient_snapshot(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Shipment::create([
            'order_id' => 42,
            'status' => 'pending',
            'recipient' => [],
        ]);
    }
}
