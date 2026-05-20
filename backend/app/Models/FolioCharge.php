<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolioCharge extends Model
{
    protected $fillable = [
        'booking_id',
        'charge_type',
        'description',
        'amount',
        'posted_by',
        'charged_at',
        'voided_at',
        'voided_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charged_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
