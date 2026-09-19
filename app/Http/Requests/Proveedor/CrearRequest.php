<?php

namespace App\Http\Requests\Proveedor;

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
        $empresaId = $this->user()?->empresaActiva()?->id;

        return [
            'rif' => [
                'required',
                'string',
                'max:20',
                Rule::unique('proveedores', 'rif')->where(fn ($query) => $query->where('estado', true)->where('empresa_id', $empresaId)),
            ],
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:150',
                Rule::unique('proveedores', 'nombre')->where(fn ($query) => $query->where('estado', true)->where('empresa_id', $empresaId)),
            ],
            'razon_social' => [
                'required',
                'string',
                'min:2',
                'max:150',
                Rule::unique('proveedores', 'razon_social')->where(fn ($query) => $query->where('estado', true)->where('empresa_id', $empresaId)),
            ],
            'nombre_contacto' => ['nullable', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:25'],
            'correo' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
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
            'rif.required' => 'El RIF o documento del proveedor es obligatorio.',
            'rif.unique' => 'Ya existe un proveedor registrado con este RIF.',
            'rif.max' => 'El RIF no debe superar los 20 caracteres.',
            'nombre.required' => 'El nombre comercial del proveedor es obligatorio.',
            'nombre.unique' => 'Ya existe un proveedor registrado con este nombre comercial.',
            'nombre.min' => 'El nombre comercial debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre comercial no debe superar los 150 caracteres.',
            'razon_social.required' => 'La razón social o nombre legal es obligatoria.',
            'razon_social.unique' => 'Ya existe un proveedor registrado con esta razón social.',
            'razon_social.min' => 'La razón social debe tener al menos 2 caracteres.',
            'razon_social.max' => 'La razón social no debe superar los 150 caracteres.',
            'nombre_contacto.max' => 'El nombre del contacto no debe superar los 100 caracteres.',
            'telefono.max' => 'El teléfono no debe superar los 25 caracteres.',
            'correo.email' => 'El correo electrónico debe tener un formato válido.',
            'correo.max' => 'El correo no debe superar los 150 caracteres.',
            'direccion.max' => 'La dirección no debe superar los 255 caracteres.',
        ];
    }
}
