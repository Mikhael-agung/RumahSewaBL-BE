<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request as RequestFacade;

class ActivityLogService
{
    public function log(?int $userId, string $activityType, string $activityDescription): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => $userId,
            'activity_type' => $activityType,
            'activity_description' => $activityDescription,
            'ip_address' => RequestFacade::ip(),
            'user_agent' => RequestFacade::header('User-Agent'),
        ]);
    }

    public function getAll(int $perPage = 10)
    {
        return ActivityLog::with('user')
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
    }

    public function getById(int $id) {
        return ActivityLog::with('user')->findOrFail($id);
    }

}
