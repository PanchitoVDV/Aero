<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'server_id',
        'package_id',
        'type',
        'status',
        'amount',
        'setup_fee',
        'total',
        'currency',
        'billing_cycle',
        'mollie_payment_id',
        'mollie_subscription_id',
        'subscription_pending',
        'metadata',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'setup_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'metadata' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'new' => 'Nieuwe Server',
            'upgrade' => 'Upgrade',
            'downgrade' => 'Downgrade',
            'renewal' => 'Verlenging',
            default => $this->type,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'In afwachting',
            'paid' => 'Betaald',
            'failed' => 'Mislukt',
            'cancelled' => 'Geannuleerd',
            'refunded' => 'Terugbetaald',
            default => $this->status,
        };
    }
}
