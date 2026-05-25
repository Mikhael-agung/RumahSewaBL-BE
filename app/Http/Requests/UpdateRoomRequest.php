<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $roomId = $this->route('room')?->id;

        return [
            'building_id'   => 'sometimes|exists:buildings,id',
            'room_code'     => 'sometimes|string|max:50|unique:rooms,room_code,' . $roomId,
            'monthly_price' => 'sometimes|numeric|min:0',
            'room_status'   => 'sometimes|in:available,occupied,maintenance',
            'notes'         => 'nullable|string',
        ];
    }
}