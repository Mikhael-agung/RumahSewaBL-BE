<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRentalRequest;
use App\Http\Requests\UpdateRentalRequest;
use App\Models\Rental;
use App\Models\Room;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class RentalController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    public function index()
    {
        $rentals = Rental::with(['tenant', 'room.building', 'createdBy'])->latest()->get();
        return response()->json([
            'success' => true,
            'message' => 'Data penyewaan berhasil diambil',
            'data'    => $rentals,
        ]);
    }

    public function store(StoreRentalRequest $request)
    {
        $room = Room::findOrFail($request->room_id);

        if ($room->room_status !== 'available') {
            return response()->json([
                'success' => false,
                'message' => 'Kamar tidak tersedia untuk disewa',
                'data'    => null,
            ], 422);
        }

        $data = array_merge($request->validated(), [
            'created_by' => Auth::id(),
        ]);

        $rental = Rental::create($data);
        $room->update(['room_status' => 'occupied']);
        $rental->load(['tenant', 'room.building', 'createdBy']);

        $this->activityLogService->log(
            Auth::id(),
            'create_rental',
            'Membuat penyewaan baru: ' . $rental->rental_code . ' untuk penyewa ' . $rental->tenant->full_name
        );

        return response()->json([
            'success' => true,
            'message' => 'Penyewaan berhasil dibuat',
            'data'    => $rental,
        ], 201);
    }

    public function show(Rental $rental)
    {
        $rental->load(['tenant', 'room.building', 'createdBy', 'payments']);
        return response()->json([
            'success' => true,
            'message' => 'Detail penyewaan berhasil diambil',
            'data'    => $rental,
        ]);
    }

    public function update(UpdateRentalRequest $request, Rental $rental)
    {
        $oldStatus = $rental->rental_status;
        $rental->update($request->validated());

        $newStatus = $rental->fresh()->rental_status;
        if ($oldStatus === 'active' && in_array($newStatus, ['ended', 'cancelled'])) {
            $rental->room->update(['room_status' => 'available']);
        }

        $rental->load(['tenant', 'room.building', 'createdBy']);

        $this->activityLogService->log(
            Auth::id(),
            'update_rental',
            'Memperbarui penyewaan: ' . $rental->rental_code . ' (status: ' . $oldStatus . ' → ' . $newStatus . ')'
        );

        return response()->json([
            'success' => true,
            'message' => 'Penyewaan berhasil diperbarui',
            'data'    => $rental,
        ]);
    }

    public function destroy(Rental $rental)
    {
        if ($rental->rental_status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Penyewaan aktif tidak bisa dihapus, ubah status terlebih dahulu',
                'data'    => null,
            ], 422);
        }

        $code = $rental->rental_code;
        $rental->delete();

        $this->activityLogService->log(
            Auth::id(),
            'delete_rental',
            'Menghapus penyewaan: ' . $code
        );

        return response()->json([
            'success' => true,
            'message' => 'Penyewaan berhasil dihapus',
            'data'    => null,
        ]);
    }
}