<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'room_id',
        'previous_status',
        'new_status',
        'changed_by',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
