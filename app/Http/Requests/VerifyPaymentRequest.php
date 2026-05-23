<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // action bisa 'verify' atau 'reject' — rejection_reason wajib kalau reject
        $action = $this->route('action') ?? $this->input('action');

        return [
            'rejection_reason' => 'required_if:action,reject|nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'rejection_reason.required_if' => 'Alasan penolakan wajib diisi',
        ];
    }
}