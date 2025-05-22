<?php

namespace App\Models;

use App\Events\OrderStatusUpdated;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updated(function (Order $order) {
            if ($order->isDirty('status')) {
                OrderStatusUpdated::dispatch($order);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
