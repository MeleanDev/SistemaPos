<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ClienteRapidoRequest extends BaseRequest
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
            'cedula' => ['required', 'string', 'max:20'],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:25'],
            'correo' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'tipo_cliente' => ['nullable', 'string', 'in:detal,mayorista'],
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
            'cedula.required' => 'La cédula o RIF del cliente es obligatoria.',
            'cedula.max' => 'La cédula o RIF no debe superar los 20 caracteres.',
            'nombre.required' => 'El nombre del cliente es obligatorio.',
            'nombre.max' => 'El nombre no debe superar los 100 caracteres.',
            'apellido.required' => 'El apellido del cliente es obligatorio.',
            'apellido.max' => 'El apellido no debe superar los 100 caracteres.',
            'correo.email' => 'El correo electrónico debe ser una dirección válida.',
            'tipo_cliente.in' => 'El tipo de cliente debe ser detal o mayorista.',
        ];
    }
}
