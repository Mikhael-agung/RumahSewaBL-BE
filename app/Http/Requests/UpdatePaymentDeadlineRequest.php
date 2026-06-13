<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentDeadlineRequest extends FormRequest
{
    /**
     * Allow the request for all users.
     *
     * This request is unconditionally authorized.
     *
     * @return bool Always `true` to authorize the request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for updating a payment deadline.
     *
     * Specifies that `deadline_date` must be present and parseable as a valid date.
     *
     * @return array<string,string> An associative array mapping field names to their validation rule strings.
     */
    public function rules(): array
    {
        return [
            'deadline_date' => 'required|date',
        ];
    }
}