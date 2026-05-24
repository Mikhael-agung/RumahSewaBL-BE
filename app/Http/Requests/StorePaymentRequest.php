<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_month' => 'required|integer|min:1|max:12',
            'payment_year'  => 'required|integer|min:2020|max:2099',
            'amount'        => 'required|numeric|min:1',
            'notes'         => 'nullable|string|max:500',
            'proof_file'    => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'proof_file.required' => 'Bukti pembayaran wajib diupload',
            'proof_file.mimes'    => 'File harus berformat JPG, PNG, atau PDF',
            'proof_file.max'      => 'Ukuran file maksimal 5MB',
            'payment_month.required' => 'Bulan pembayaran wajib diisi',
            'payment_year.required'  => 'Tahun pembayaran wajib diisi',
            'amount.required'        => 'Jumlah pembayaran wajib diisi',
        ];
    }
}