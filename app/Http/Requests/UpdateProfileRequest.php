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
     * Validation rules for updating own tenant profile (phone_number & email).
     *
     * Kedua field bersifat "sometimes" (boleh update salah satu doang), tapi
     * begitu diisi harus valid. Email unique terhadap tabel tenants,
     * dikecualikan dari baris milik tenant yang sedang login sendiri.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        $tenantId = $this->user()->tenant->id ?? null;

        return [
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
            'phone_number.regex' => 'Format nomor HP tidak valid',
            'email.unique'       => 'Email sudah dipakai penyewa lain',
        ];
    }
}