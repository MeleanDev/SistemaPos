<?php

namespace App\Http\Requests\Setup;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InstalacionInicialRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Datos de la Empresa Matriz
            'empresa_tipo_cedula' => ['required', 'string'],
            'empresa_cedula_numero' => ['required', 'string', 'min:4', 'max:20'],
            'empresa_nombre' => ['required', 'string', 'min:2', 'max:150'],
            'empresa_razon_social' => ['required', 'string', 'min:2', 'max:150'],
            'empresa_direccion' => ['required', 'string', 'min:3', 'max:255'],
            'empresa_codigo_pais' => ['nullable', 'string', 'max:10'],
            'empresa_telefono_numero' => ['nullable', 'string', 'max:20'],
            'empresa_correo' => ['nullable', 'email', 'max:150'],
            'empresa_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],

            // Datos del SuperAdministrador
            'admin_tipo_cedula' => ['required', 'string'],
            'admin_cedula_numero' => ['required', 'string', 'min:4', 'max:20'],
            'admin_nombre' => ['required', 'string', 'min:2', 'max:100'],
            'admin_apellido' => ['required', 'string', 'min:2', 'max:100'],
            'admin_email' => ['required', 'email', 'max:150'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
            'admin_password_confirmation' => ['required', 'string', 'min:8'],

            // Métodos de Pago
            'metodos_pago' => ['nullable', 'array'],
            'metodos_pago.*' => ['string', 'max:100'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'empresa_cedula_numero.required' => 'El RIF o documento fiscal de la empresa es obligatorio.',
            'empresa_cedula_numero.min' => 'El RIF de la empresa debe tener al menos 4 caracteres.',
            'empresa_nombre.required' => 'El nombre comercial de la empresa es obligatorio.',
            'empresa_nombre.min' => 'El nombre comercial debe tener al menos 2 caracteres.',
            'empresa_razon_social.required' => 'La razón social de la empresa es obligatoria.',
            'empresa_razon_social.min' => 'La razón social debe tener al menos 2 caracteres.',
            'empresa_direccion.required' => 'La dirección fiscal de la empresa es obligatoria.',
            'empresa_correo.email' => 'El correo de la empresa debe tener un formato válido.',
            'empresa_logo.image' => 'El logo debe ser un archivo de imagen válido.',
            'empresa_logo.mimes' => 'El logo debe estar en formato JPG, PNG, WEBP o SVG.',
            'empresa_logo.max' => 'El logo no debe superar los 2MB de tamaño.',

            'admin_cedula_numero.required' => 'La cédula del SuperAdministrador es obligatoria.',
            'admin_cedula_numero.min' => 'La cédula debe tener al menos 4 caracteres.',
            'admin_nombre.required' => 'El nombre del SuperAdministrador es obligatorio.',
            'admin_nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'admin_apellido.required' => 'El apellido del SuperAdministrador es obligatorio.',
            'admin_apellido.min' => 'El apellido debe tener al menos 2 caracteres.',
            'admin_email.required' => 'El correo electrónico del SuperAdministrador es obligatorio.',
            'admin_email.email' => 'El correo electrónico debe tener un formato válido.',
            'admin_password.required' => 'La contraseña del SuperAdministrador es obligatoria.',
            'admin_password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'admin_password.confirmed' => 'La confirmación de la contraseña no coincide.',
            'admin_password_confirmation.required' => 'Debe confirmar la contraseña.',
            'admin_password_confirmation.min' => 'La confirmación de contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
