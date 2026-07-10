<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for updating own profile (username, full_name, phone_number, email).
     *
     * Semua field bersifat "sometimes" (boleh update salah satu/beberapa doang), tapi
     * begitu diisi harus valid. Email unique terhadap tabel tenants (dikecualikan tenant
     * sendiri), username unique terhadap tabel users (dikecualikan user sendiri).
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant->id ?? null;
        $userId   = $this->user()->id ?? null;

        return [
            'username' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'full_name'    => ['sometimes', 'required', 'string', 'max:150'],
            'phone_number' => ['sometimes', 'required', 'string', 'max:30', 'regex:/^[0-9+\-\s]+$/'],
            'email'        => [
                'sometimes',
                'required',
                'email',
                'max:150',
                Rule::unique('tenants', 'email')->ignore($tenantId),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique'     => 'Username sudah dipakai user lain',
            'username.alpha_dash' => 'Username hanya boleh huruf, angka, strip, dan underscore',
            'phone_number.regex'  => 'Format nomor HP tidak valid',
            'email.unique'        => 'Email sudah dipakai penyewa lain',
        ];
    }
}