<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBuildingRequest;
use App\Http\Requests\UpdateBuildingRequest;
use App\Models\Building;

class BuildingController extends Controller
{

    /**
     * @return \Illuminate\Http\JsonResponse
     * 
     * @response array{
     *   success: bool,
     *   message: string,
     *   data: array<int, array{
     *     id: int,
     *     building_code: string,
     *     building_name: string,
     *     building_address: string|null,
     *     description: string|null,
     *     rooms_count: int,
     *     created_at: string,
     *     updated_at: string,
     *     deleted_at: string|null
     *   }>
     * }
     */ 

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

        return response()->json([
            'success' => true,
            'message' => 'Gedung berhasil diperbarui',
            'data'    => $building,
        ]);
    }

    public function destroy(Building $building)
    {
        // Cek apakah ada kamar aktif
        if ($building->rooms()->whereNull('deleted_at')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Gedung tidak bisa dihapus karena masih memiliki kamar aktif',
                'data'    => null,
            ], 422);
        }

        $building->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gedung berhasil dihapus',
            'data'    => null,
        ]);
    }
}
