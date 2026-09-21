<?php

namespace App\Http\Requests\Usuario;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CambiarEmpresaRequest extends FormRequest
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
            'empresa_id' => [
                'required',
                'integer',
                Rule::exists('empresas', 'id')->where(fn ($query) => $query->where('estado', true)),
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
            'empresa_id.required' => 'La empresa seleccionada es obligatoria.',
            'empresa_id.integer' => 'El identificador de la empresa debe ser un número entero.',
            'empresa_id.exists' => 'La empresa seleccionada no existe o se encuentra inactiva.',
        ];
    }
}
