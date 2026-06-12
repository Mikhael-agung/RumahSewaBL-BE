<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected ActivityLogService $activityLogService;

    /**
     * Initialize the controller with authentication and activity logging services.
     *
     * @param AuthService $authService Service responsible for authentication operations.
     * @param ActivityLogService $activityLogService Service used to record user activity events.
     */
    public function __construct(AuthService $authService, ActivityLogService $activityLogService)
    {
        $this->authService = $authService;
        $this->activityLogService = $activityLogService;
    }

    /**
     * Authenticate a user and return the authentication result as JSON.
     *
     * When authentication succeeds, records a `login` activity for the authenticated user.
     *
     * @param \App\Http\Requests\LoginRequest $request The validated login request containing `username` and `password`.
     * @return \Illuminate\Http\JsonResponse The JSON payload produced by the authentication service (success flag, data, message, etc.) with the HTTP status code supplied by the service.
     */
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

    /**
     * Invalidate the current authentication token and return the logout result as JSON.
     *
     * If the token's user can be identified before invalidation and the logout succeeds, records a "logout" activity for that user.
     *
     * @return \Illuminate\Http\JsonResponse JSON payload returned by the authentication service (with the `status` field removed) and the HTTP status code provided by the service.
     */
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

    /**
     * Return the authenticated user's profile payload as a JSON HTTP response.
     *
     * @return \Illuminate\Http\JsonResponse JSON response containing the data returned by AuthService::me with the `status` field removed; the HTTP status code is taken from the removed `status` value.
     */
    public function me(Request $request)
    {
        $result = $this->authService->me();

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }
}