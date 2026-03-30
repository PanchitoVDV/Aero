<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'virtfusion_package_id',
        'name',
        'description',
        'category',
        'memory',
        'storage',
        'cpu_cores',
        'traffic',
        'network_speed_in',
        'network_speed_out',
        'price_monthly',
        'price_quarterly',
        'price_yearly',
        'setup_fee',
        'is_active',
        'is_featured',
        'sort_order',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'features' => 'array',
            'price_monthly' => 'decimal:2',
            'price_quarterly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'setup_fee' => 'decimal:2',
        ];
    }

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getPriceForCycle(string $cycle): float
    {
        return match ($cycle) {
            'quarterly' => (float) ($this->price_quarterly ?? $this->price_monthly * 3),
            'yearly' => (float) ($this->price_yearly ?? $this->price_monthly * 12),
            default => (float) $this->price_monthly,
        };
    }

    public function getFormattedMemoryAttribute(): string
    {
        return $this->memory >= 1024
            ? round($this->memory / 1024, 1) . ' GB'
            : $this->memory . ' MB';
    }

    public function getFormattedStorageAttribute(): string
    {
        return $this->storage . ' GB';
    }

    public function getFormattedTrafficAttribute(): string
    {
        if ($this->traffic === 0) return 'Onbeperkt';
        return $this->traffic >= 1000
            ? round($this->traffic / 1000, 1) . ' TB'
            : $this->traffic . ' GB';
    }
}
