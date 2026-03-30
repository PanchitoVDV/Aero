<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_id',
        'invoice_number',
        'description',
        'status',
        'subtotal',
        'tax_rate',
        'tax_amount',
        'total',
        'currency',
        'due_date',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'due_date' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public static function generateNumber(): string
    {
        $year = date('Y');
        $last = static::where('invoice_number', 'like', "INV-{$year}-%")
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($last) {
            $parts = explode('-', $last->invoice_number);
            $sequence = (int) end($parts) + 1;
        }

        return sprintf('INV-%s-%05d', $year, $sequence);
    }
}
