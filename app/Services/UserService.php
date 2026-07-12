<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Daftar semua user beserta role-nya, terbaru duluan.
     */
    public function list(): array
    {
        $users = User::with('role')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (User $user) => $this->formatUser($user));

        return [
            'success' => true,
            'data'    => $users,
            'status'  => 200,
        ];
    }

    /**
     * Bikin user baru (administrator/manager/penyewa).
     *
     * Catatan: ini cuma bikin baris di tabel `users`. Kalau role-nya 'penyewa',
     * data tenant (nama, no HP, email) tetap harus dilengkapi terpisah lewat
     * endpoint /tenants (TenantController) — bukan tanggung jawab service ini.
     */
    public function create(string $username, string $password, string $roleName): array
    {
        $roleId = Role::where('name', $roleName)->value('id');

        if (!$roleId) {
            return [
                'success' => false,
                'message' => 'Role tidak ditemukan',
                'status'  => 422,
            ];
        }

        $user = User::create([
            'username'  => $username,
            'password'  => Hash::make($password),
            'role_id'   => $roleId,
            'is_active' => true,
        ]);

        $user->load('role');

        return [
            'success' => true,
            'message' => 'User berhasil dibuat',
            'data'    => $this->formatUser($user),
            'status'  => 201,
        ];
    }

    /**
     * Toggle status aktif/nonaktif user. Dipakai buat "menonaktifkan" akun
     * tanpa menghapusnya (soft-deactivate, bukan delete).
     */
    public function toggleActive(int $id): array
    {
        $user = User::find($id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User tidak ditemukan',
                'status'  => 404,
            ];
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return [
            'success' => true,
            'message' => $user->is_active ? 'User diaktifkan kembali' : 'User dinonaktifkan',
            'data'    => $this->formatUser($user->load('role')),
            'status'  => 200,
        ];
    }

    /**
     * Reset password user lain oleh administrator (gak perlu tau password lama).
     */
    public function resetPassword(int $id, string $newPassword): array
    {
        $user = User::find($id);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'User tidak ditemukan',
                'status'  => 404,
            ];
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return [
            'success' => true,
            'message' => 'Password user berhasil direset',
            'status'  => 200,
        ];
    }

    private function formatUser(User $user): array
    {
        return [
            'id'         => $user->id,
            'username'   => $user->username,
            'role'       => $user->role->name ?? null,
            'is_active'  => (bool) $user->is_active,
            'created_at' => $user->created_at,
        ];
    }
}