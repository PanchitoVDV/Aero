<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResourcePricing extends Model
{
    protected $table = 'resource_pricing';

    protected $fillable = [
        'resource_type',
        'label',
        'unit',
        'price_per_unit',
        'min_value',
        'max_value',
        'step',
        'default_value',
    ];

    protected function casts(): array
    {
        return [
            'price_per_unit' => 'decimal:4',
        ];
    }

    public static function getPricing(): array
    {
        return self::all()->keyBy('resource_type')->toArray();
    }

    public static function calculateMonthlyPrice(int $ramGb, int $cpuCores, int $storageGb, int $ipv4): float
    {
        $pricing = self::all()->keyBy('resource_type');

        $total = 0;

        if ($pricing->has('base_price')) {
            $total += (float) $pricing['base_price']->price_per_unit;
        }
        if ($pricing->has('ram_gb')) {
            $total += $ramGb * (float) $pricing['ram_gb']->price_per_unit;
        }
        if ($pricing->has('cpu_core')) {
            $total += $cpuCores * (float) $pricing['cpu_core']->price_per_unit;
        }
        if ($pricing->has('storage_gb')) {
            $total += $storageGb * (float) $pricing['storage_gb']->price_per_unit;
        }
        if ($pricing->has('ipv4')) {
            $total += $ipv4 * (float) $pricing['ipv4']->price_per_unit;
        }

        return round($total, 2);
    }

    public static function calculateForCycle(int $ramGb, int $cpuCores, int $storageGb, int $ipv4, string $cycle): float
    {
        $monthly = self::calculateMonthlyPrice($ramGb, $cpuCores, $storageGb, $ipv4);

        return match ($cycle) {
            'quarterly' => round($monthly * 3 * 0.95, 2),
            'yearly' => round($monthly * 12 * 0.85, 2),
            default => $monthly,
        };
    }
}
