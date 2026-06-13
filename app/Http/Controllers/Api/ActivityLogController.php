<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;

class ActivityLogController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    public function index()
    {
        $logs = $this->activityLogService->getAll();

        return response()->json([
            'success' => true,
            'data'    => $logs,
        ], 200);
    }

    public function show(ActivityLog $activityLog)
    {
        $log = $this->activityLogService->getById($activityLog->id);

        return response()->json([
            'success' => true,
            'data'    => $log,
        ], 200);
    }
}