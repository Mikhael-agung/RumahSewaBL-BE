<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;


class AuthService
{
    public function login(string $username, string  $password): array
    {
        $user = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.username', $username)
            ->where('users.is_active', 1)
            ->select('users.*', 'roles.name as role_name')
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return
                [
                    'success' => false,
                    'message' => 'Username atau password salah',
                    'status' => 401
                ];
        }

        $userModel = User::find($user->id);
        $token = JWTAuth::fromUser($userModel);

        return [
            'success' => true,
            'message' => 'Login berhasil',
            'token'   => $token,
            'user'    => [
                'id'       => $user->id,
                'username' => $user->username,
                'role'     => $user->role_name,
            ],
            'status' => 200,
        ];
    }

    public function logout(): array
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return [
                'success' => true,
                'message' => 'Logout berhasil',
                'status' => 200,
            ];
        } catch (TokenInvalidException $e) {
            return [
                'success' => false,
                'message' => 'Token tidak valid',
                'status' => 401,
            ];
        } catch (JWTException $e) {
            return [
                'success' => false,
                'message' => 'token tidak ditemukan',
                'status' => 400,
            ];
        }
    }

    public function me(): array
    {
        $user = JWTAuth::parseToken()->authenticate();
        $role = DB::table('roles')->where('id', $user->role_id)->value('name');

        return [
            'success' => true,
            'user'    => [
                'id'       => $user->id,
                'username' => $user->username,
                'role'     => $role,
            ],
            'status' => 200,
        ];
    }

    public function refresh(): array
    {
        try {
            $newToken = JWTAuth::refresh(JWTAuth::getToken());

            return [
                'success' => true,
                'message' => 'Token berhasil diperbarui',
                'token'   => $newToken,
                'status'  => 200,
            ];
        } catch (TokenInvalidException $e) {
            return [
                'success' => false,
                'message' => 'Token tidak valid',
                'status'  => 401,
            ];
        } catch (JWTException $e) {
            return [
                'success' => false,
                'message' => 'Token tidak ditemukan',
                'status'  => 400,
            ];
        }
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword): array
    {
        if (!Hash::check($currentPassword, $user->password)) {
            return [
                'success' => false,
                'message' => 'Password lama tidak sesuai',
                'status'  => 422,
            ];
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return [
            'success' => true,
            'message' => 'Password berhasil diubah',
            'status'  => 200,
        ];
    }

    public function updateProfile(User $user, array $data): array
    {
        $tenant = $user->tenant;

        if (!$tenant) {
            return [
                'success' => false,
                'message' => 'Data penyewa tidak ditemukan',
                'status'  => 404,
            ];
        }

        if(array_key_exists('username', $data)) {
            $user->username = $data['username'];
            $user->save();
        }

        $tenant->array_intersect_key($data, array_flip(['full_name', 'phone_number', 'email']));

        if(!empty($tenantData)) {
            $tenant->fill($tenantData);
            $tenant->save();
        }

        return [
            'success' => true,
            'message' => 'Profil berhasil diperbarui',
            'data'    => [
                'username'     => $user->username,
                'full_name'    => $tenant->full_name,
                'phone_number' => $tenant->phone_number,
                'email'        => $tenant->email,
            ],
            'status' => 200,
        ];
    }
}
