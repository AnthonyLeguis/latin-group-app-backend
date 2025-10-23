<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullName' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'zipCode' => ['required', 'string', 'max:20'],
            'serviceMedical' => ['boolean'],
            'serviceDental' => ['boolean'],
            'serviceAccidents' => ['boolean'],
            'serviceLife' => ['boolean'],
            'acceptSms' => ['nullable', 'boolean'],
            'recaptcha_token' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'fullName.required' => 'El nombre completo es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe ser válido.',
            'phone.required' => 'El teléfono es obligatorio.',
            'zipCode.required' => 'El código postal es obligatorio.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $services = [
                $this->input('serviceMedical', false),
                $this->input('serviceDental', false),
                $this->input('serviceAccidents', false),
                $this->input('serviceLife', false),
            ];
            if (!in_array(true, $services, true)) {
                $validator->errors()->add('services', 'Debes seleccionar al menos un servicio.');
            }
        });
    }
}
