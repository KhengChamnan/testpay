<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tran_id',
        'payment_status',
        'amount',
        'currency',
        'user_id',
        'booking_id',
        'payment_option',
        'qr_string',
        'deeplink',
        'callback_data',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_EXPIRED = 'expired';

    // Helper methods
    public function isPending()
    {
        return $this->payment_status === self::STATUS_PENDING;
    }

    public function isPaid()
    {
        return $this->payment_status === self::STATUS_PAID;
    }

    public function markAsPaid($callbackData = null)
    {
        $this->update([
            'payment_status' => self::STATUS_PAID,
            'paid_at' => now(),
            'callback_data' => $callbackData ? json_encode($callbackData) : null,
        ]);
    }

    public function markAsFailed($callbackData = null)
    {
        $this->update([
            'payment_status' => self::STATUS_FAILED,
            'callback_data' => $callbackData ? json_encode($callbackData) : null,
        ]);
    }
}
