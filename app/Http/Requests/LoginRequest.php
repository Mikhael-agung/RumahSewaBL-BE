<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for the login request.
     *
     * Returns an array mapping input field names to their validation rules.
     *
     * @return array<string, string> Validation rules keyed by field name (e.g., `username` and `password` are required and must be strings).
     */
    public function rules(): array
    {
        return [
            'username' => 'required|string',
            'password' => 'required|string',
        ];
    }
}
