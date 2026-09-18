<?php

namespace App\Http\Requests\Cliente;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearRequest extends FormRequest
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
            'cedula' => [
                'required',
                'string',
                'max:20',
                Rule::unique('clientes', 'cedula')->where(fn ($query) => $query->where('estado', true)),
            ],
            'nombre' => ['required', 'string', 'min:2', 'max:100'],
            'apellido' => ['required', 'string', 'min:2', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'tipo_cliente' => ['required', 'string', 'in:detal,mayorista'],
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
            'cedula.unique' => 'Ya existe un cliente registrado con esta cédula o RIF.',
            'cedula.max' => 'La cédula o RIF no debe superar los 20 caracteres.',
            'nombre.required' => 'El nombre del cliente es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no debe superar los 100 caracteres.',
            'apellido.required' => 'El apellido del cliente es obligatorio.',
            'apellido.min' => 'El apellido debe tener al menos 2 caracteres.',
            'apellido.max' => 'El apellido no debe superar los 100 caracteres.',
            'telefono.max' => 'El teléfono no debe superar los 20 caracteres.',
            'correo.email' => 'El correo electrónico debe tener un formato válido.',
            'correo.max' => 'El correo no debe superar los 150 caracteres.',
            'direccion.max' => 'La dirección no debe superar los 255 caracteres.',
            'tipo_cliente.required' => 'Debe seleccionar el tipo de cliente (Detal o Mayorista).',
            'tipo_cliente.in' => 'El tipo de cliente seleccionado no es válido.',
        ];
    }
}
