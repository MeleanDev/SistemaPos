<?php

namespace App\Http\Requests\Configuracion;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $empresaId = $this->user()?->empresaActiva()?->id;

        return [
            'rif' => [
                'required',
                'string',
                'min:5',
                'max:20',
                Rule::unique('empresas', 'rif')->ignore($empresaId)->where(fn ($q) => $q->where('estado', true)),
            ],
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:150',
                Rule::unique('empresas', 'nombre')->ignore($empresaId)->where(fn ($q) => $q->where('estado', true)),
            ],
            'razon_social' => [
                'required',
                'string',
                'min:2',
                'max:150',
            ],
            'direccion' => [
                'required',
                'string',
                'min:5',
                'max:255',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:25',
            ],
            'correo' => [
                'nullable',
                'email',
                'max:150',
            ],
            'logo' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,webp,svg',
                'max:2048',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rif.required' => 'El RIF o documento fiscal es obligatorio.',
            'rif.unique' => 'Este RIF ya se encuentra registrado en otra empresa activa.',
            'nombre.required' => 'El nombre comercial de la empresa es obligatorio.',
            'nombre.unique' => 'Este nombre comercial ya pertenece a otra empresa activa.',
            'razon_social.required' => 'La razón social es obligatoria.',
            'direccion.required' => 'La dirección fiscal es obligatoria.',
            'correo.email' => 'El correo electrónico ingresado no tiene un formato válido.',
            'logo.image' => 'El logo debe ser un archivo de imagen válido.',
            'logo.mimes' => 'El logo debe estar en formato PNG, JPG, JPEG, WEBP o SVG.',
            'logo.max' => 'El logo no puede pesar más de 2MB.',
        ];
    }
}
