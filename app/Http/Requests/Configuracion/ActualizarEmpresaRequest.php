<?php

namespace App\Http\Requests\Configuracion;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ActualizarEmpresaRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $empresaId = $this->empresaId();

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
            'rif.min' => 'El RIF debe tener al menos 5 caracteres.',
            'rif.max' => 'El RIF no debe superar los 20 caracteres.',
            'nombre.required' => 'El nombre comercial de la empresa es obligatorio.',
            'nombre.unique' => 'Este nombre comercial ya pertenece a otra empresa activa.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no debe superar los 150 caracteres.',
            'razon_social.required' => 'La razón social es obligatoria.',
            'razon_social.min' => 'La razón social debe tener al menos 2 caracteres.',
            'razon_social.max' => 'La razón social no debe superar los 150 caracteres.',
            'direccion.required' => 'La dirección fiscal es obligatoria.',
            'direccion.min' => 'La dirección debe tener al menos 5 caracteres.',
            'direccion.max' => 'La dirección no debe superar los 255 caracteres.',
            'correo.email' => 'El correo electrónico ingresado no tiene un formato válido.',
            'correo.max' => 'El correo no debe superar los 150 caracteres.',
            'logo.image' => 'El logo debe ser un archivo de imagen válido.',
            'logo.mimes' => 'El logo debe estar en formato PNG, JPG, JPEG, WEBP o SVG.',
            'logo.max' => 'El logo no puede pesar más de 2MB.',
        ];
    }
}
