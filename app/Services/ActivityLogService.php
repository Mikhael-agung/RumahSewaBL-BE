<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request as RequestFacade;

class ActivityLogService
{
    /**
     * Create a new activity log record for a user and request context.
     *
     * Captures the provided activity details and stores the request IP address and User-Agent header.
     *
     * @param int|null $userId The ID of the user associated with the activity, or null for anonymous actions.
     * @param string $activityType A short identifier for the activity type (e.g., "login", "update").
     * @param string $activityDescription A human-readable description of the activity.
     * @return \App\Models\ActivityLog The created ActivityLog model instance.
     */
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

    /**
     * Retrieve paginated activity logs with their associated user, ordered by newest first.
     *
     * @param int $perPage Number of items per page.
     * @return \Illuminate\Pagination\LengthAwarePaginator Paginated ActivityLog models with the `user` relation loaded, ordered by `created_at` descending.
     */
    public function getAll(int $perPage = 10)
    {
        return ActivityLog::with('user')
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);
    }

    /**
     * Retrieve an activity log by its primary key and eager-load the related user.
     *
     * @param int $id The primary key of the ActivityLog.
     * @return \App\Models\ActivityLog The found ActivityLog model with the `user` relationship loaded.
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException If no ActivityLog exists with the given id.
     */
    public function getById(int $id) {
        return ActivityLog::with('user')->findOrFail($id);
    }

}
