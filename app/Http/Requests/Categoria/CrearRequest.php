<?php

namespace App\Http\Requests\Categoria;

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
            'codigo' => [
                'required',
                'string',
                'min:2',
                'max:50',
                Rule::unique('categorias', 'codigo')->where(fn ($query) => $query->where('estado', true)->where('empresa_id', $empresaId)),
            ],
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:150',
                Rule::unique('categorias', 'nombre')->where(fn ($query) => $query->where('estado', true)->where('empresa_id', $empresaId)),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
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
            'codigo.required' => 'El código de la categoría es obligatorio.',
            'codigo.unique' => 'Ya existe una categoría registrada con este código en tu empresa.',
            'codigo.min' => 'El código debe tener al menos 2 caracteres.',
            'codigo.max' => 'El código no debe superar los 50 caracteres.',
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.unique' => 'Ya existe una categoría registrada con este nombre en tu empresa.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no debe superar los 150 caracteres.',
            'descripcion.max' => 'La descripción no debe superar los 255 caracteres.',
        ];
    }
}
