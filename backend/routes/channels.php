<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('hotel.dashboard', function ($user) {
    return in_array($user->role, ['admin', 'front_desk', 'manager'], true);
});

Broadcast::channel('hotel.housekeeping', function ($user) {
    return in_array($user->role, ['admin', 'manager', 'housekeeping'], true);
});
