<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TwoFactorEnableRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'channel' => 'required|in:email,telegram',
            'identifier' => 'required|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'channel.required' => 'Channel verifikasi harus dipilih.',
            'channel.in' => 'Channel verifikasi tidak valid.',
            'identifier.required' => 'Identifier harus diisi.',
            'identifier.string' => 'Identifier harus berupa string.',
        ];
    }
}