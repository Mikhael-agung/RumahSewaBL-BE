<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\ActivityLogService;

class ActivityLogController extends Controller
{
    protected ActivityLogService $activityLogService;

    /**
     * Initialize the controller with the ActivityLogService.
     *
     * @param ActivityLogService $activityLogService Service used to retrieve and manage activity logs.
     */
    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Return a JSON list of all activity logs.
     *
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
     *
     * @return \Illuminate\Http\JsonResponse JSON response with `success` set to `true` and `data` containing the collection of activity logs.
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
     * Retrieve a single activity log by its route-bound model.
     *
     * Returns a JSON response with a `success` flag and `data` containing the requested activity log.
     *
     * @param \App\Models\ActivityLog $activityLog The activity log instance provided by route model binding.
     * @return \Illuminate\Http\JsonResponse JSON with `success => true` and `data => ActivityLog`.
     *
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