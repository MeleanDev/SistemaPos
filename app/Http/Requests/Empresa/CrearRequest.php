<?php

namespace App\Http\Requests\Empresa;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class CrearRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rif' => [
                'required',
                'string',
                'max:20',
                Rule::unique('empresas', 'rif')->where(fn ($query) => $query->where('estado', true)),
            ],
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:150',
                Rule::unique('empresas', 'nombre')->where(fn ($query) => $query->where('estado', true)),
            ],
            'razon_social' => [
                'required',
                'string',
                'min:2',
                'max:150',
                Rule::unique('empresas', 'razon_social')->where(fn ($query) => $query->where('estado', true)),
            ],
            'direccion' => [
                'required',
                'string',
                'max:255',
            ],
            'telefono' => ['nullable', 'string', 'max:25'],
            'correo' => ['nullable', 'email', 'max:150'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'maneja_motos' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rif.required' => 'El RIF o documento fiscal de la empresa es obligatorio.',
            'rif.unique' => 'Ya existe una empresa registrada con este RIF.',
            'rif.max' => 'El RIF no debe superar los 20 caracteres.',
            'nombre.required' => 'El nombre comercial de la empresa es obligatorio.',
            'nombre.unique' => 'Ya existe una empresa registrada con este nombre comercial.',
            'nombre.min' => 'El nombre comercial debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre comercial no debe superar los 150 caracteres.',
            'razon_social.required' => 'La razón social o nombre legal es obligatoria.',
            'razon_social.unique' => 'Ya existe una empresa registrada con esta razón social.',
            'razon_social.min' => 'La razón social debe tener al menos 2 caracteres.',
            'razon_social.max' => 'La razón social no debe superar los 150 caracteres.',
            'direccion.required' => 'La dirección fiscal de la empresa es obligatoria.',
            'direccion.max' => 'La dirección no debe superar los 255 caracteres.',
            'telefono.max' => 'El teléfono no debe superar los 25 caracteres.',
            'correo.email' => 'El correo electrónico debe tener un formato válido.',
            'correo.max' => 'El correo no debe superar los 150 caracteres.',
            'logo.image' => 'El archivo seleccionado debe ser una imagen.',
            'logo.mimes' => 'El logo debe ser de formato JPG, PNG, WEBP o SVG.',
            'logo.max' => 'El logo no debe superar los 2MB de tamaño.',
        ];
    }
}
