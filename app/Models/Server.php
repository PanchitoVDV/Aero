<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Server extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'package_id',
        'virtfusion_server_id',
        'name',
        'hostname',
        'status',
        'power_status',
        'ip_address',
        'os_template',
        'custom_ram',
        'custom_cpu',
        'custom_storage',
        'custom_ipv4',
        'monthly_price',
        'billing_cycle',
        'next_due_date',
        'suspended_at',
        'suspension_reason',
    ];

    protected function casts(): array
    {
        return [
            'next_due_date' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnline(): bool
    {
        return $this->power_status === 'online';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => 'bg-green-100 text-green-800',
            'pending' => 'bg-yellow-100 text-yellow-800',
            'building' => 'bg-blue-100 text-blue-800',
            'suspended' => 'bg-red-100 text-red-800',
            'error' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getPowerBadgeAttribute(): string
    {
        return match ($this->power_status) {
            'online' => 'bg-green-100 text-green-800',
            'offline' => 'bg-gray-100 text-gray-800',
            default => 'bg-yellow-100 text-yellow-800',
        };
    }
}
