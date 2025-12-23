<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateReservationRequest extends FormRequest
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
        $type = $this->input('type');
        $serviceIdRule = ['required', 'integer'];
        
        // Validate service_id exists in appropriate table based on type
        if ($type === 'layanan') {
            $serviceIdRule[] = 'exists:layanans,id';
        } elseif ($type === 'paket') {
            $serviceIdRule[] = 'exists:paket_layanans,id';
        }
        
        return [
            'service_id' => $serviceIdRule,
            'type' => ['required', 'string', Rule::in(['layanan', 'paket'])],
            'sesi_id' => ['required', 'integer', 'exists:sesis,id'],
            'tanggal_reservasi' => ['required', 'date', 'after_or_equal:today'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'baby_id' => ['nullable', 'integer', 'exists:bayis,id'],
            'baby_data' => ['nullable', 'array', 'required_without:baby_id'],
            'baby_data.nama' => ['required_with:baby_data', 'string', 'max:255'],
            'baby_data.tanggal_lahir' => ['required_with:baby_data', 'date', 'before:today'],
            'baby_data.jenis_kelamin' => ['required_with:baby_data', 'string', Rule::in(['L', 'P'])],
            'baby_data.berat_lahir' => ['nullable', 'numeric', 'min:0'],
            'baby_data.berat_sekarang' => ['nullable', 'numeric', 'min:0'],
            'baby_data.is_temporary' => ['nullable', 'boolean'],
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
            'service_id.required' => 'Service ID wajib diisi.',
            'service_id.exists' => 'Service tidak ditemukan.',
            'type.required' => 'Tipe service wajib diisi.',
            'type.in' => 'Tipe service harus layanan atau paket.',
            'sesi_id.required' => 'Sesi wajib diisi.',
            'sesi_id.exists' => 'Sesi tidak ditemukan.',
            'tanggal_reservasi.required' => 'Tanggal reservasi wajib diisi.',
            'tanggal_reservasi.date' => 'Format tanggal tidak valid.',
            'tanggal_reservasi.after_or_equal' => 'Tanggal reservasi harus hari ini atau setelahnya.',
            'baby_id.exists' => 'Data bayi tidak ditemukan.',
            'baby_data.nama.required_with' => 'Nama bayi wajib diisi.',
            'baby_data.tanggal_lahir.required_with' => 'Tanggal lahir bayi wajib diisi.',
            'baby_data.tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'baby_data.jenis_kelamin.required_with' => 'Jenis kelamin bayi wajib diisi.',
            'baby_data.jenis_kelamin.in' => 'Jenis kelamin harus L atau P.',
        ];
    }
}

