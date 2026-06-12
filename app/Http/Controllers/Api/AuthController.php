<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login($request->username, $request->password);

        $status = $result['status'];
        unset($result['status']);

        return response()->json($result, $status);
    }

    public function logout(Request $request)
    {
        $result = $this->authService->logout();

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
}