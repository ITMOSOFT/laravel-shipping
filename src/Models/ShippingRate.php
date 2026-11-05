<?php
namespace ItmoSoft\Shipping\Models;
class ShippingRate extends BaseModel
{
    protected $guarded = [];
    protected $casts = ['amount' => 'decimal:4', 'min_value' => 'decimal:4', 'max_value' => 'decimal:4'];
    public function method() { return $this->belongsTo(ShippingMethod::class, 'shipping_method_id'); }
    public function zone() { return $this->belongsTo(ShippingZone::class, 'zone_id'); }
}
