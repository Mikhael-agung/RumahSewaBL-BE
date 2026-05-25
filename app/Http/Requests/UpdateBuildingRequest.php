<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBuildingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $buildingId = $this->route('building')?->id;

        return [
            'building_code'    => 'sometimes|string|max:50|unique:buildings,building_code,' . $buildingId,
            'building_name'    => 'sometimes|string|max:255',
            'building_address' => 'sometimes|string',
            'description'      => 'nullable|string',
        ];
    }
}