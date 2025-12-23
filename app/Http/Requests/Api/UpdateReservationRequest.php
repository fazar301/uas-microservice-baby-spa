<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sesi_id' => ['sometimes', 'integer', 'exists:sesis,id'],
            'tanggal_reservasi' => ['sometimes', 'date', 'after_or_equal:today'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'baby_id' => ['nullable', 'integer', 'exists:bayis,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sesi_id.exists' => 'Sesi tidak ditemukan.',
            'tanggal_reservasi.date' => 'Format tanggal tidak valid.',
            'tanggal_reservasi.after_or_equal' => 'Tanggal reservasi harus hari ini atau setelahnya.',
            'baby_id.exists' => 'Data bayi tidak ditemukan.',
        ];
    }
}

