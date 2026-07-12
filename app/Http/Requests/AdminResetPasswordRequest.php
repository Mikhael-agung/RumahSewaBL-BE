<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for an administrator resetting another user's password.
     *
     * Gak perlu current_password soalnya ini admin yang reset akun orang lain,
     * bukan user ganti password sendiri (itu udah ada di ChangePasswordRequest).
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'new_password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.min'       => 'Password baru minimal 8 karakter',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok',
        ];
    }
}