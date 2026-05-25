<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'building_id'   => 'required|exists:buildings,id',
            'room_code'     => 'required|string|max:50|unique:rooms,room_code',
            'monthly_price' => 'required|numeric|min:0',
            'room_status'   => 'required|in:available,occupied,maintenance',
            'notes'         => 'nullable|string',
        ];
    }
}