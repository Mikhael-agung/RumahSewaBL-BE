<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for creating a new user account.
     *
     * role dicocokkan ke nama role ('administrator', 'manager', 'penyewa'),
     * bukan langsung role_id, biar FE gak perlu tau ID internal role.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'username' => 'required|string|max:100|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|string|in:administrator,manager,penyewa',
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique'   => 'Username sudah dipakai, pilih yang lain',
            'password.min'      => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'role.in'            => 'Role harus salah satu dari: administrator, manager, penyewa',
        ];
    }
}