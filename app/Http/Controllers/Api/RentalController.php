<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRentalRequest;
use App\Http\Requests\UpdateRentalRequest;
use App\Models\Rental;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;

class RentalController extends Controller
{
    public function index()
    {
        $rentals = Rental::with(['tenant', 'room.building', 'createdBy'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data penyewaan berhasil diambil',
            'data'    => $rentals,
        ]);
    }

    public function store(StoreRentalRequest $request)
    {
        $room = Room::findOrFail($request->room_id);

        // Cek status kamar
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

        // Update status kamar jadi occupied
        $room->update(['room_status' => 'occupied']);

        $rental->load(['tenant', 'room.building', 'createdBy']);

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

        // Kalau status berubah jadi ended/cancelled, bebaskan kamar
        $newStatus = $rental->fresh()->rental_status;
        if ($oldStatus === 'active' && in_array($newStatus, ['ended', 'cancelled'])) {
            $rental->room->update(['room_status' => 'available']);
        }

        $rental->load(['tenant', 'room.building', 'createdBy']);

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

        $rental->delete();

        return response()->json([
            'success' => true,
            'message' => 'Penyewaan berhasil dihapus',
            'data'    => null,
        ]);
    }
}