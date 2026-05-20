<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    protected $fillable = [
        'booking_reference',
        'guest_id',
        'room_id',
        'room_type_id',
        'check_in_date',
        'check_out_date',
        'actual_check_in',
        'actual_check_out',
        'num_adults',
        'num_children',
        'status',
        'source',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_price',
        'special_requests',
        'cancellation_reason',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'actual_check_in' => 'datetime',
        'actual_check_out' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Booking $booking) {
            if (!$booking->booking_reference) {
                $date = Carbon::now()->format('Ymd');
                $count = static::whereDate('created_at', Carbon::today())->count() + 1;
                $booking->booking_reference = sprintf('SS-%s-%03d', $date, $count);
            }
        });
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function addons(): HasMany
    {
        return $this->hasMany(BookingAddon::class);
    }

    public function folioCharges(): HasMany
    {
        return $this->hasMany(FolioCharge::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
