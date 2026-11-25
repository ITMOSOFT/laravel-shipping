<?php

namespace ItmoSoft\Shipping\Models;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use ItmoSoft\Shipping\Events\ShipmentCreated;
use ItmoSoft\Shipping\Events\ShipmentStatusChanged;

class Shipment extends BaseModel
{
    protected $guarded = [];

    protected $casts = [
        'recipient' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $shipment): void {
            $statuses = (array) config('itmosoft-shipping.statuses', []);

            if (! in_array($shipment->status, $statuses, true)) {
                throw new InvalidArgumentException('Shipment status is not configured.');
            }

            if (! is_array($shipment->recipient) || $shipment->recipient === []) {
                throw new InvalidArgumentException('Shipment recipient must be a non-empty array.');
            }
        });

        static::created(fn (self $shipment) => event(new ShipmentCreated($shipment)));

        static::updated(function (self $shipment): void {
            if ($shipment->wasChanged('status')) {
                event(new ShipmentStatusChanged($shipment, $shipment->getOriginal('status')));
            }
        });
    }

    public function events()
    {
        return $this->hasMany(ShipmentEvent::class);
    }

    public function recordEvent(string $status, ?string $description = null, array $metadata = []): ShipmentEvent
    {
        $statuses = (array) config('itmosoft-shipping.statuses', []);

        if (! in_array($status, $statuses, true)) {
            throw new InvalidArgumentException('Shipment status is not configured.');
        }

        if (! $this->exists || $this->getKey() === null) {
            throw new InvalidArgumentException('Tracking events can only be recorded for a persisted shipment.');
        }

        return DB::transaction(function () use ($status, $description, $metadata): ShipmentEvent {
            /** @var self $shipment */
            $shipment = static::query()->lockForUpdate()->findOrFail($this->getKey());

            if ($shipment->status !== $status) {
                $shipment->status = $status;
                $shipment->save();
            }

            $event = $shipment->events()->create([
                'status' => $status,
                'description' => $description,
                'metadata' => $metadata,
            ]);

            $this->setRawAttributes($shipment->getAttributes(), true);
            $this->exists = true;
            $this->wasRecentlyCreated = false;

            return $event;
        });
    }
}
