<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guest extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'id_type',
        'id_number',
        'address',
        'city',
        'country',
        'notes',
        'loyalty_tier',
        'total_stays',
    ];

    protected $casts = [
        'total_stays' => 'integer',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
