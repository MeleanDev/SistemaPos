<?php

namespace App\Http\Requests\Almacen;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarRequest extends FormRequest
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
        $id = $this->route('id');

        return [
            'codigo' => [
                'required',
                'string',
                'min:2',
                'max:50',
                Rule::unique('almacenes', 'codigo')
                    ->ignore($id)
                    ->where(fn ($query) => $query->where('estado', true)->where('empresa_id', $empresaId)),
            ],
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:150',
                Rule::unique('almacenes', 'nombre')
                    ->ignore($id)
                    ->where(fn ($query) => $query->where('estado', true)->where('empresa_id', $empresaId)),
            ],
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
            'codigo.required' => 'El código del almacén es obligatorio.',
            'codigo.unique' => 'Ya existe un almacén registrado con este código en tu empresa.',
            'codigo.min' => 'El código debe tener al menos 2 caracteres.',
            'codigo.max' => 'El código no debe superar los 50 caracteres.',
            'nombre.required' => 'El nombre del almacén es obligatorio.',
            'nombre.unique' => 'Ya existe un almacén registrado con este nombre en tu empresa.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no debe superar los 150 caracteres.',
            'direccion.max' => 'La dirección no debe superar los 255 caracteres.',
        ];
    }
}
