<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Tenant;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('user')->latest()->get();

        return response()->json([
            'success' => true,
            'message' => 'Data penyewa berhasil diambil',
            'data'    => $tenants,
        ]);
    }

    public function store(StoreTenantRequest $request)
    {
        $tenant = Tenant::create($request->validated());
        $tenant->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Penyewa berhasil ditambahkan',
            'data'    => $tenant,
        ], 201);
    }

    public function show(Tenant $tenant)
    {
        $tenant->load('user', 'rentals.room.building');

        return response()->json([
            'success' => true,
            'message' => 'Detail penyewa berhasil diambil',
            'data'    => $tenant,
        ]);
    }

    public function update(UpdateTenantRequest $request, Tenant $tenant)
    {
        $tenant->update($request->validated());
        $tenant->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Penyewa berhasil diperbarui',
            'data'    => $tenant,
        ]);
    }

    public function destroy(Tenant $tenant)
    {
        if ($tenant->rentals()->where('rental_status', 'active')->whereNull('deleted_at')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Penyewa tidak bisa dihapus karena masih memiliki penyewaan aktif',
                'data'    => null,
            ], 422);
        }

        $tenant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Penyewa berhasil dihapus',
            'data'    => null,
        ]);
    }
}