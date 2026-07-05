<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationController extends Controller {
    
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService) {
        
        $this->notificationService = $notificationService;
    }

    public function index(): JsonResponse {
        try {

            $user = JWTAuth::parseToken()->authenticate();
            $notifications = $this->notificationService->getForUser($user->id);

            return response()->json([
                'success' => true,
                'data'    => $notifications,
            ]);

        }catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function unreadCount(): JsonResponse {
        
        try {
        
            $user = JWTAuth::parseToken()->authenticate();
            $unreadCount = $this->notificationService->unreadCount($user->id);

            return response()->json([
                'success' => true,
                'data'    => ['unread_count' => $unreadCount],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);


        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function markAsRead(): JsonResponse {
        
        try {
            $user = JWTAuth::parseToken()->authenticate();
            $updated = $this->notificationService->markAllAsRead($user->id);

            return response()->json([
                'success' => true,
                'data '    => ['updated_count' => $updated],
                'message' => 'Semua notifikasi berhasil ditandai sebagai dibaca',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}