<?php

namespace App\Http\Requests\Usuario;

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
            'name' => [
                'required',
                'string',
                'max:20',
                Rule::unique('users', 'name')->where(fn ($query) => $query->where('estado', true)),
            ],
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'apellido' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->where(fn ($query) => $query->where('estado', true)),
            ],
            'password' => [
                'required',
                'string',
                'min:6',
            ],
            'rol' => [
                'required',
                'string',
                'exists:roles,name',
            ],
            'empresas' => [
                'nullable',
                'array',
            ],
            'empresas.*' => [
                'integer',
                'exists:empresas,id',
            ],
            'permisos' => [
                'nullable',
                'array',
            ],
            'permisos.*' => [
                'string',
                'exists:permissions,name',
            ],
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
            'name.required' => 'La identificación / cédula del usuario es obligatoria.',
            'name.unique' => 'Ya existe un usuario registrado con esta identificación / cédula.',
            'name.max' => 'La identificación no debe superar los 20 caracteres.',
            'nombre.required' => 'El nombre del usuario es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no debe superar los 100 caracteres.',
            'apellido.required' => 'El apellido del usuario es obligatorio.',
            'apellido.min' => 'El apellido debe tener al menos 2 caracteres.',
            'apellido.max' => 'El apellido no debe superar los 100 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico debe tener un formato válido.',
            'email.unique' => 'Ya existe un usuario registrado con este correo electrónico.',
            'email.max' => 'El correo electrónico no debe superar los 150 caracteres.',
            'password.required' => 'La contraseña de acceso es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'rol.required' => 'Debe seleccionar un rol para el usuario.',
            'rol.exists' => 'El rol seleccionado no es válido en el sistema.',
            'empresas.array' => 'El formato de empresas seleccionadas es inválido.',
            'empresas.*.exists' => 'Una de las empresas seleccionadas no existe en el sistema.',
        ];
    }
}
