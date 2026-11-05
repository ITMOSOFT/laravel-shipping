<?php
namespace ItmoSoft\Shipping\Events;
use ItmoSoft\Shipping\Models\Shipment;
class ShipmentCreated { public function __construct(public readonly Shipment $shipment) {} }
