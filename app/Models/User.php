<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'virtfusion_user_id',
        'mollie_customer_id',
        'mollie_mandate_id',
        'company',
        'phone',
        'address',
        'city',
        'postal_code',
        'country',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function activeServers()
    {
        return $this->servers()->where('status', 'active');
    }

    public function hasMollieMandate(): bool
    {
        return !empty($this->mollie_mandate_id);
    }
}
