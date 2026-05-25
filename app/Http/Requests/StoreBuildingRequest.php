<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBuildingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'building_code'    => 'required|string|max:50|unique:buildings,building_code',
            'building_name'    => 'required|string|max:255',
            'building_address' => 'required|string',
            'description'      => 'nullable|string',
        ];
    }
}