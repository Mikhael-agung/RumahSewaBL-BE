<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for changing password.
     *
     * current_password: wajib diisi, dicocokkan manual di service layer terhadap hash yang tersimpan.
     * new_password: minimal 8 karakter, wajib beda dari current_password, wajib dikonfirmasi.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password'      => 'required|string|min:8|different:current_password|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'new_password.different' => 'Password baru tidak boleh sama dengan password lama',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok',
            'new_password.min'       => 'Password baru minimal 8 karakter',
        ];
    }
}