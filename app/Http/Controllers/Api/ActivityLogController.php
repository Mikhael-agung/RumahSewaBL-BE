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

    /**
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "data": {}
     * }
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     * @response 403 scenario="Forbidden - role bukan administrator" {
     *   "message": "Forbidden"
     * }
     */
    public function index()
    {
        $logs = $this->activityLogService->getAll();

        return response()->json([
            'success' => true,
            'data'    => $logs,
        ], 200);
    }

    /**
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "data": {}
     * }
     * @response 401 scenario="Unauthenticated" {
     *   "message": "Unauthenticated."
     * }
     * @response 403 scenario="Forbidden - role bukan administrator" {
     *   "message": "Forbidden"
     * }
     * @response 404 scenario="Activity log tidak ditemukan" {
     *   "message": "No query results for model [App\\Models\\ActivityLog] {id}"
     * }
     */
    public function show(ActivityLog $activityLog)
    {
        $log = $this->activityLogService->getById($activityLog->id);

        return response()->json([
            'success' => true,
            'data'    => $log,
        ], 200);
    }
}