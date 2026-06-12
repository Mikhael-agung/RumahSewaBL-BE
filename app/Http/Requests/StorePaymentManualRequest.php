<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentManualRequest extends FormRequest
{
    /**
     * Sanitize the incoming `notes` field by removing HTML tags and merge the cleaned value into the request before validation.
     *
     * If `notes` is present, its value is replaced with a tag-stripped string so validation and subsequent handling receive the sanitized text.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('notes')) {
            $this->merge([
                'notes' => strip_tags($this->input('notes')),
            ]);
        }
    }

    /**
     * Always authorizes the request.
     *
     * @return bool `true` if the request is authorized, `false` otherwise (always `true`).
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for a store manual payment request.
     *
     * Validates required payment fields and optional notes with constraints:
     * - `rental_id`: must reference an existing rental ID.
     * - `payment_month`: integer between 1 and 12.
     * - `payment_year`: integer between 2000 and 2100.
     * - `amount`: numeric and at least 0.
     * - `payment_date`: valid date.
     * - `notes`: optional string up to 500 characters.
     *
     * @return array<string,string> Array mapping request field names to their validation rules.
     */
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