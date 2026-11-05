<?php
namespace ItmoSoft\Shipping\Events;
use ItmoSoft\Shipping\Models\Shipment;
class ShipmentStatusChanged { public function __construct(public readonly Shipment $shipment, public readonly ?string $oldStatus) {} }
