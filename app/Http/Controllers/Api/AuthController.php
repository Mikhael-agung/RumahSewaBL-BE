<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Services\AuthService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected ActivityLogService $activityLogService;

    public function __construct(AuthService $authService, ActivityLogService $activityLogService)
    {
        $this->authService = $authService;
        $this->activityLogService = $activityLogService;
    }

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->username, $request->password);

        if ($result['success']) {
            $this->activityLogService->log(
                $result['user']['id'],
                'login',
                'User ' . $result['user']['username'] . ' login ke sistem'
            );
        }

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function logout(Request $request)
    {
        // Ambil user sebelum token di-invalidate
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Throwable $e) {
            $user = null;
        }

        $result = $this->authService->logout();

        if ($result['success'] && $user) {
            $this->activityLogService->log(
                $user->id,
                'logout',
                'User ' . $user->username . ' logout dari sistem'
            );
        }

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function me(Request $request)
    {
        $result = $this->authService->me();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function refresh(Request $request)
    {
        $result = $this->authService->refresh();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function changePassword(changePasswordRequest $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $result = $this->authService->changePassword(
            $user,
            $request->current_password,
            $request->new_password
        );
        

        if ($result['success']) {
            $this->activityLogService->log(
                $user->id,
                'change_password',
                'User ' . $user->username . ' mengubah password'
            );
        }

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }


    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = JWTAuth::parseToken()->authenticate();

        $result = $this->authService->updateProfile(
            $user,
            $request->only(['username', 'full_name', 'phone_number', 'email'])
        );

        if ($result['success']) {
            $this->activityLogService->log(
                $user->id,
                'update_profile',
                'User ' . $user->username . ' memperbarui profil'
            );
        }

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}
