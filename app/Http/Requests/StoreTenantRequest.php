<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'tenant_code'  => 'required|string|max:50|unique:tenants,tenant_code',
            'full_name'    => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'email'        => 'required|email|unique:tenants,email',
        ];
    }
}