<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentDeadlineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_month' => 'required|integer|min:1|max:12',
            'payment_year'  => 'required|integer|min:2000|max:2100',
            'deadline_date' => 'required|date',
        ];
    }
}