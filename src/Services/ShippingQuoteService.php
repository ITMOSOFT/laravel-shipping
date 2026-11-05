<?php
namespace ItmoSoft\Shipping\Services;
use ItmoSoft\Shipping\Models\ShippingMethod;
use ItmoSoft\Shipping\Models\ShippingZone;
class ShippingQuoteService
{
    /** @return array<int, array{method_id:int,method:string,code:string,amount:float,currency:string,rate_type:string}> */
    public function quote(array $destination, array $context): array
    {
        $subtotal = (float) ($context['subtotal'] ?? 0);
        $weight = (float) ($context['weight'] ?? 0);
        $currency = (string) ($context['currency'] ?? config('itmosoft-shipping.currency', 'USD'));
        $quotes = [];
        $zones = ShippingZone::query()->where('is_active', true)->with('rules')->get()->filter(fn ($zone) => $this->zoneMatches($zone, $destination));
        foreach (ShippingMethod::query()->where('is_active', true)->with('rates')->get() as $method) {
            foreach ($method->rates->whereIn('zone_id', $zones->pluck('id')) as $rate) {
                $basis = match ($rate->type) { 'weight' => $weight, 'subtotal' => $subtotal, default => 0.0 };
                if ($rate->min_value !== null && $basis < (float) $rate->min_value) continue;
                if ($rate->max_value !== null && $basis > (float) $rate->max_value) continue;
                $amount = match ($rate->type) { 'weight' => (float) $rate->amount * $weight, 'subtotal' => $subtotal * ((float) $rate->amount / 100), default => (float) $rate->amount };
                if ($rate->currency !== $currency) continue;
                $quotes[] = ['method_id' => $method->id, 'method' => $method->name, 'code' => $method->code, 'amount' => round($amount, 2), 'currency' => $currency, 'rate_type' => $rate->type];
            }
        }
        return $quotes;
    }
    private function zoneMatches(ShippingZone $zone, array $destination): bool
    {
        if ($zone->rules->isEmpty()) return false;
        foreach ($zone->rules as $rule) {
            $actual = match ($rule->type) { 'country' => $destination['country'] ?? null, 'region' => $destination['region'] ?? null, 'postal_code' => $destination['postal_code'] ?? null, default => null };
            if ($actual !== null && strcasecmp((string) $actual, (string) $rule->value) === 0) return true;
        }
        return false;
    }
}
