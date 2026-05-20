<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RoomType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'base_rate',
        'max_occupancy',
        'description',
        'amenities',
    ];

    protected $casts = [
        'base_rate' => 'decimal:2',
        'amenities' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (RoomType $roomType) {
            if (!$roomType->slug && $roomType->name) {
                $roomType->slug = Str::slug($roomType->name);
            }
        });
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function rateOverrides(): HasMany
    {
        return $this->hasMany(RateOverride::class);
    }
}
