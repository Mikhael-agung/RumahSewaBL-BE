<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentManualRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('notes')) {
            $this->merge([
                'notes' => strip_tags($this->input('notes')),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rental_id'     => 'required|integer|exists:rentals,id',
            'payment_month' => 'required|integer|min:1|max:12',
            'payment_year'  => 'required|integer|min:2000|max:2100',
            'amount'        => 'required|numeric|min:0',
            'payment_date'  => 'required|date',
            'notes'         => 'nullable|string|max:500',
        ];
    }
}