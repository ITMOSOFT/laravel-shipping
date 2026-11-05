<?php
namespace ItmoSoft\Shipping\Models;
class ShippingZoneRule extends BaseModel
{
    protected $guarded = [];
    public function zone() { return $this->belongsTo(ShippingZone::class, 'zone_id'); }
}
