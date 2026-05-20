<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public function log(string $action, ?Model $model = null, ?string $description = null, ?Request $request = null): void
    {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'description' => $description,
            'ip_address' => $request?->ip(),
            'created_at' => now(),
        ]);
    }
}
