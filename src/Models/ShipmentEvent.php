<?php
namespace ItmoSoft\Shipping\Models;
class ShipmentEvent extends BaseModel
{
    protected $guarded = [];
    protected $casts = ['metadata' => 'array'];
    public function shipment() { return $this->belongsTo(Shipment::class); }
}
