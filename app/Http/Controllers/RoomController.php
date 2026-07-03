<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class RoomController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    public function index()
    {
        $rooms = Room::with('building')->latest()->get();
        return response()->json([
            'success' => true,
            'message' => 'Data kamar berhasil diambil',
            'data'    => $rooms,
        ]);
    }

    public function store(StoreRoomRequest $request)
    {
        $room = Room::create($request->validated());
        $room->load('building');

        $this->activityLogService->log(
            Auth::id(),
            'create_room',
            'Menambahkan kamar baru: ' . $room->room_number . ' di gedung ' . $room->building->building_name
        );

        return response()->json([
            'success' => true,
            'message' => 'Kamar berhasil ditambahkan',
            'data'    => $room,
        ], 201);
    }

    public function show(Room $room)
    {
        $room->load('building', 'rentals.tenant');
        return response()->json([
            'success' => true,
            'message' => 'Detail kamar berhasil diambil',
            'data'    => $room,
        ]);
    }

    public function update(UpdateRoomRequest $request, Room $room)
    {
        $room->update($request->validated());
        $room->load('building');

        $this->activityLogService->log(
            Auth::id(),
            'update_room',
            'Memperbarui kamar: ' . $room->room_number . ' di gedung ' . $room->building->building_name
        );

        return response()->json([
            'success' => true,
            'message' => 'Kamar berhasil diperbarui',
            'data'    => $room,
        ]);
    }

    public function destroy(Room $room)
    {
        if ($room->rentals()->where('rental_status', 'active')->whereNull('deleted_at')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kamar tidak bisa dihapus karena masih ada penyewaan aktif',
                'data'    => null,
            ], 422);
        }

        $roomNumber = $room->room_number;
        $buildingName = $room->building->building_name;
        $room->delete();

        $this->activityLogService->log(
            Auth::id(),
            'delete_room',
            'Menghapus kamar: ' . $roomNumber . ' di gedung ' . $buildingName
        );

        return response()->json([
            'success' => true,
            'message' => 'Kamar berhasil dihapus',
            'data'    => null,
        ]);
    }
}