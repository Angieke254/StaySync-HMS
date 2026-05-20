<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RateOverride extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'room_type_id',
        'date',
        'override_rate',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'date' => 'date',
        'override_rate' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
