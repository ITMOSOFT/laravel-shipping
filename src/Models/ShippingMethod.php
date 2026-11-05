<?php
namespace ItmoSoft\Shipping\Models;
class ShippingMethod extends BaseModel
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function rates() { return $this->hasMany(ShippingRate::class); }
}
