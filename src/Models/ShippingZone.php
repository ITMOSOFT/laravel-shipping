<?php
namespace ItmoSoft\Shipping\Models;
class ShippingZone extends BaseModel
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function rules() { return $this->hasMany(ShippingZoneRule::class, 'zone_id'); }
}
