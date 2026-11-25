<?php

namespace ItmoSoft\Shipping\Services;

use InvalidArgumentException;
use ItmoSoft\Shipping\Models\ShippingMethod;
use ItmoSoft\Shipping\Models\ShippingZone;

class ShippingQuoteService
{
    /**
     * @return array<int, array{method_id:int,method:string,code:string,amount:float,currency:string,rate_type:string}>
     */
    public function quote(array $destination, array $context): array
    {
        $subtotal = $this->nonNegativeNumber($context['subtotal'] ?? 0, 'subtotal');
        $weight = $this->nonNegativeNumber($context['weight'] ?? 0, 'weight');
        $currency = strtoupper(trim((string) ($context['currency'] ?? config('itmosoft-shipping.currency', 'USD'))));

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Currency must be a three-letter ISO-style code.');
        }

        $zones = ShippingZone::query()
            ->where('is_active', true)
            ->with('rules')
            ->get()
            ->filter(fn (ShippingZone $zone) => $this->zoneMatches($zone, $destination));

        if ($zones->isEmpty()) {
            return [];
        }

        $quotesByMethod = [];

        foreach (ShippingMethod::query()->where('is_active', true)->with('rates')->get() as $method) {
            foreach ($method->rates->whereIn('zone_id', $zones->pluck('id')) as $rate) {
                if (! in_array($rate->type, ['flat', 'weight', 'subtotal'], true)) {
                    continue;
                }

                $amountPerUnit = $this->nonNegativeNumber($rate->amount, 'rate amount');
                $min = $rate->min_value === null ? null : $this->nonNegativeNumber($rate->min_value, 'rate minimum');
                $max = $rate->max_value === null ? null : $this->nonNegativeNumber($rate->max_value, 'rate maximum');

                if ($min !== null && $max !== null && $min > $max) {
                    throw new InvalidArgumentException('Rate min_value cannot exceed max_value.');
                }

                if (strtoupper(trim((string) $rate->currency)) !== $currency) {
                    continue;
                }

                // Flat-rate thresholds are based on order subtotal; other rate types
                // use the same quantity that drives their calculation.
                $basis = match ($rate->type) {
                    'weight' => $weight,
                    'subtotal', 'flat' => $subtotal,
                };

                if (($min !== null && $basis < $min) || ($max !== null && $basis > $max)) {
                    continue;
                }

                $calculated = match ($rate->type) {
                    'weight' => $amountPerUnit * $weight,
                    'subtotal' => $subtotal * ($amountPerUnit / 100),
                    'flat' => $amountPerUnit,
                };

                if (! is_finite($calculated) || $calculated < 0) {
                    throw new InvalidArgumentException('Calculated shipping amount must be a finite, non-negative number.');
                }

                $candidate = [
                    'method_id' => (int) $method->id,
                    'method' => (string) $method->name,
                    'code' => (string) $method->code,
                    'amount' => round($calculated, 2),
                    'currency' => $currency,
                    'rate_type' => $rate->type,
                ];

                // A quote returns one deterministic (lowest-cost) option per method.
                if (! isset($quotesByMethod[$method->id]) || $candidate['amount'] < $quotesByMethod[$method->id]['amount']) {
                    $quotesByMethod[$method->id] = $candidate;
                }
            }
        }

        return array_values($quotesByMethod);
    }

    private function nonNegativeNumber(mixed $value, string $field): float
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("The {$field} must be numeric.");
        }

        $number = (float) $value;

        if (! is_finite($number) || $number < 0) {
            throw new InvalidArgumentException("The {$field} must be a finite, non-negative number.");
        }

        return $number;
    }

    private function zoneMatches(ShippingZone $zone, array $destination): bool
    {
        if ($zone->rules->isEmpty()) {
            return false;
        }

        // Values within one rule type are alternatives; different rule types
        // are cumulative constraints (e.g. country AND region).
        foreach ($zone->rules->groupBy('type') as $type => $rules) {
            $destinationValue = match ($type) {
                'country' => $destination['country'] ?? null,
                'region' => $destination['region'] ?? null,
                'postal_code' => $destination['postal_code'] ?? null,
                default => null,
            };

            if ($destinationValue === null || trim((string) $destinationValue) === '') {
                return false;
            }

            $matched = $rules->contains(
                fn ($rule) => strcasecmp(trim((string) $destinationValue), trim((string) $rule->value)) === 0
            );

            if (! $matched) {
                return false;
            }
        }

        return true;
    }
}
