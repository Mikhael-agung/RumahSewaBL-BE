<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'rental_code'   => 'required|string|max:50|unique:rentals,rental_code',
            'tenant_id'     => 'required|exists:tenants,id',
            'room_id'       => 'required|exists:rooms,id',
            'start_date'    => 'required|date',
            'end_date'      => 'required|date|after:start_date',
            'rental_status' => 'required|in:active,ended,cancelled',
        ];
    }
}