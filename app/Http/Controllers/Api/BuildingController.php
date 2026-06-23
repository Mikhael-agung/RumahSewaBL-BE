<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use App\Models\Building;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class BuildingController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    public function index()
    {
        $buildings = Building::withCount('rooms')->latest()->get();
        return response()->json([
            'success' => true,
            'message' => 'Data gedung berhasil diambil',
            'data'    => $buildings,
        ]);
    }

    public function store(StoreBuildingRequest $request)
    {
        $building = Building::create($request->validated());

        $this->activityLogService->log(
            Auth::id(),
            'create_building',
            'Menambahkan gedung baru: ' . $building->building_name
        );

        return response()->json([
            'success' => true,
            'message' => 'Gedung berhasil ditambahkan',
            'data'    => $building,
        ], 201);
    }

    public function show(Building $building)
    {
        $building->load('rooms');

        return response()->json([
            'success' => true,
            'message' => 'Detail gedung berhasil diambil',
            'data'    => $building,
        ]);
    }

    public function update(UpdateBuildingRequest $request, Building $building)
    {
        $building->update($request->validated());

        $this->activityLogService->log(
            Auth::id(),
            'update_building',
            'Memperbarui gedung: ' . $building->building_name
        );

        return response()->json([
            'success' => true,
            'message' => 'Gedung berhasil diperbarui',
            'data'    => $building,
        ]);
    }

    public function destroy(Building $building)
    {
        if ($building->rooms()->whereNull('deleted_at')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Gedung tidak bisa dihapus karena masih memiliki kamar aktif',
                'data'    => null,
            ], 422);
        }

        $name = $building->building_name;
        $building->delete();

        $this->activityLogService->log(
            Auth::id(),
            'delete_building',
            'Menghapus gedung: ' . $name
        );

        return response()->json([
            'success' => true,
            'message' => 'Gedung berhasil dihapus',
            'data'    => null,
        ]);
    }
}