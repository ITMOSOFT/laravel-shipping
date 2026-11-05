<?php
namespace ItmoSoft\Shipping\Models;
use ItmoSoft\Shipping\Events\ShipmentCreated;
use ItmoSoft\Shipping\Events\ShipmentStatusChanged;
class Shipment extends BaseModel
{
    protected $guarded = [];
    protected $casts = ['recipient' => 'array', 'metadata' => 'array'];
    protected static function booted(): void
    {
        static::created(fn (self $shipment) => event(new ShipmentCreated($shipment)));
        static::updated(function (self $shipment) {
            if ($shipment->wasChanged('status')) event(new ShipmentStatusChanged($shipment, $shipment->getOriginal('status')));
        });
    }
    public function events() { return $this->hasMany(ShipmentEvent::class); }
    public function recordEvent(string $status, ?string $description = null, array $metadata = []): ShipmentEvent
    {
        $this->forceFill(['status' => $status])->save();
        return $this->events()->create(['status' => $status, 'description' => $description, 'metadata' => $metadata]);
    }
}
