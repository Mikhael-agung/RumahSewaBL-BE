<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id;

        return [
            'tenant_code'  => 'sometimes|string|max:50|unique:tenants,tenant_code,' . $tenantId,
            'user_id'      => 'nullable|exists:users,id',
            'full_name'    => 'sometimes|string|max:255',
            'phone_number' => 'sometimes|string|max:20',
            'email'        => 'sometimes|email|unique:tenants,email,' . $tenantId,
        ];
    }
}