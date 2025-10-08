<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class OtpSetupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'channel' => 'required|in:email,telegram',
            'identifier' => 'required|string|max:255',
        ];

        // Validasi khusus berdasarkan channel
        if ($this->input('channel') === 'email') {
            $rules['identifier'] = 'required|email|max:255';
        } elseif ($this->input('channel') === 'telegram') {
            $rules['identifier'] = 'required|string|max:255|regex:/^[0-9]+$/';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'channel.required' => 'Channel pengiriman wajib dipilih',
            'channel.in' => 'Channel harus email atau telegram',
            'identifier.required' => 'Identifier wajib diisi',
            'identifier.string' => 'Identifier harus berupa teks',
            'identifier.max' => 'Identifier maksimal 255 karakter',
            'identifier.email' => 'Format email tidak valid',
            'identifier.regex' => 'Telegram Chat ID harus berupa angka',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes(): array
    {
        return [
            'channel' => 'channel pengiriman',
            'identifier' => $this->input('channel') === 'email' ? 'email' : 'Telegram Chat ID',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $channel = $this->input('channel');
            $identifier = $this->input('identifier');

            if ($channel === 'telegram' && $identifier) {
                // Validasi panjang Chat ID Telegram (biasanya 9-10 digit)
                if (strlen($identifier) < 6 || strlen($identifier) > 15) {
                    $validator->errors()->add('identifier', 'Telegram Chat ID tidak valid (6-15 digit)');
                }
            }
        });
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Data tidak valid',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
