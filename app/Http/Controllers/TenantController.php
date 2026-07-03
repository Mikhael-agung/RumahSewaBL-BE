<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTenantRequest;
use App\Http\Requests\UpdateTenantRequest;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

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
        $validated = $request->validated();

        $username = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $validated['tenant_code']));
        $plainPassword = Str::password(8, false, true, false, false);

        [$tenant, $user] = DB::transaction(function () use ($validated, $username, $plainPassword) {
            $penyewaRoleId = Role::where('name', 'penyewa')->value('id');

            $user = User::create([
                'role_id'   => $penyewaRoleId,
                'username'  => $username,
                'password'  => Hash::make($plainPassword),
                'is_active' => 1,
            ]);

            $tenant = Tenant::create([
                'tenant_code'  => $validated['tenant_code'],
                'user_id'      => $user->id,
                'full_name'    => $validated['full_name'],
                'phone_number' => $validated['phone_number'],
                'email'        => $validated['email'],
            ]);

            return [$tenant, $user];
        });

        $tenant->load('user');

        $this->activityLogService->log(
            Auth::id(),
            'create_tenant',
            'Menambahkan penyewa baru: ' . $tenant->full_name . ' (' . $tenant->tenant_code . ')'
        );

        return response()->json([
            'success' => true,
            'message' => 'Penyewa berhasil ditambahkan',
            'data'    => $tenant,
            'account' => [
                'username' => $user->username,
                'password' => $plainPassword,
                'note'     => 'Simpan kredensial ini, password tidak dapat ditampilkan kembali setelah ini.',
            ],
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

        $this->activityLogService->log(
            Auth::id(),
            'update_tenant',
            'Memperbarui data penyewa: ' . $tenant->full_name . ' (' . $tenant->tenant_code . ')'
        );

        return response()->json([
            'success' => true,
            'message' => 'Penyewa berhasil diperbarui',
            'data'    => $tenant,
        ]);
    }

    public function destroy(Tenant $tenant)
    {
        // Bug fix: 'aktif' → 'active'
        if ($tenant->rentals()->where('rental_status', 'active')->whereNull('deleted_at')->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Penyewa tidak bisa dihapus karena masih memiliki penyewaan aktif',
                'data'    => null,
            ], 422);
        }

        $name = $tenant->full_name;
        $code = $tenant->tenant_code;
        $tenant->delete();

        $this->activityLogService->log(
            Auth::id(),
            'delete_tenant',
            'Menghapus penyewa: ' . $name . ' (' . $code . ')'
        );

        return response()->json([
            'success' => true,
            'message' => 'Penyewa berhasil dihapus',
            'data'    => null,
        ]);
    }
}