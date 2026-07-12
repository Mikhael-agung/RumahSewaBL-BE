<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminResetPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Services\ActivityLogService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    protected UserService $userService;
    protected ActivityLogService $activityLogService;

    public function __construct(UserService $userService, ActivityLogService $activityLogService)
    {
        $this->userService = $userService;
        $this->activityLogService = $activityLogService;
    }

    public function index(Request $request)
    {
        $result = $this->userService->list();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function store(StoreUserRequest $request)
    {
        $result = $this->userService->create(
            $request->username,
            $request->password,
            $request->role
        );

        if ($result['success']) {
            $admin = JWTAuth::parseToken()->authenticate();
            $this->activityLogService->log(
                $admin->id,
                'create_user',
                'Membuat akun user baru: ' . $request->username . ' (role: ' . $request->role . ')'
            );
        }

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function toggleActive(int $id)
    {
        $result = $this->userService->toggleActive($id);

        if ($result['success']) {
            $admin = JWTAuth::parseToken()->authenticate();
            $this->activityLogService->log(
                $admin->id,
                'toggle_user_active',
                $result['message'] . ' (user ID: ' . $id . ')'
            );
        }

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function resetPassword(AdminResetPasswordRequest $request, int $id)
    {
        $result = $this->userService->resetPassword($id, $request->new_password);

        if ($result['success']) {
            $admin = JWTAuth::parseToken()->authenticate();
            $this->activityLogService->log(
                $admin->id,
                'reset_user_password',
                'Mereset password user ID: ' . $id
            );
        }

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}