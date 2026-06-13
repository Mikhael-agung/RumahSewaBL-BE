<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentDeadlineRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     *
     * @return bool `true` if the request is authorized, `false` otherwise.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get validation rules for storing a payment deadline.
     *
     * Defines required validations for `payment_month`, `payment_year`, and `deadline_date`.
     *
     * @return array An associative array mapping input field names to Laravel validation rule strings.
     */
    public function rules(): array
    {
        return [
            'payment_month' => 'required|integer|min:1|max:12',
            'payment_year'  => 'required|integer|min:2000|max:2100',
            'deadline_date' => 'required|date',
        ];
    }
}