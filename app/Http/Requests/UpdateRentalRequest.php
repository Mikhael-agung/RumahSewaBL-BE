<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRentalRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $rentalId = $this->route('rental')?->id;

        return [
            'rental_code'   => 'sometimes|string|max:50|unique:rentals,rental_code,' . $rentalId,
            'tenant_id'     => 'sometimes|exists:tenants,id',
            'room_id'       => 'sometimes|exists:rooms,id',
            'start_date'    => 'sometimes|date',
            'end_date'      => 'sometimes|date|after:start_date',
            'rental_status' => 'sometimes|in:active,ended,cancelled',
        ];
    }
}