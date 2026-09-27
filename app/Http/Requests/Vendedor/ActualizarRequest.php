<?php

namespace App\Http\Requests\Vendedor;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ActualizarRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $empresaId = $this->empresaId();
        $vendedorId = $this->route('id') ?? $this->route('vendedor') ?? $this->input('id');

        return [
            'tipo_documento' => ['required', 'string', 'in:V,E,J,G,P'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'documento' => [
                'required',
                'string',
                'max:25',
                Rule::unique('vendedores', 'documento')
                    ->where(fn ($query) => $query->where('empresa_id', $empresaId))
                    ->ignore($vendedorId),
            ],
            'nombre' => ['required', 'string', 'min:2', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'correo' => ['nullable', 'email', 'max:150'],
            'comision_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'estado' => ['nullable', 'boolean'],
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
            'tipo_documento.required' => 'El tipo de documento es obligatorio.',
            'tipo_documento.in' => 'El tipo de documento seleccionado no es válido.',
            'documento.required' => 'El número de documento es obligatorio.',
            'documento.unique' => 'Ya existe otro vendedor con este documento en la empresa.',
            'documento.max' => 'El documento no debe superar los 25 caracteres.',
            'nombre.required' => 'El nombre completo del vendedor es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no debe superar los 150 caracteres.',
            'telefono.max' => 'El teléfono no debe superar los 30 caracteres.',
            'correo.email' => 'El correo electrónico debe tener un formato válido.',
            'correo.max' => 'El correo electrónico no debe superar los 150 caracteres.',
            'comision_porcentaje.required' => 'El porcentaje de comisión es obligatorio.',
            'comision_porcentaje.numeric' => 'El porcentaje de comisión debe ser un número.',
            'comision_porcentaje.min' => 'El porcentaje de comisión no puede ser menor a 0%.',
            'comision_porcentaje.max' => 'El porcentaje de comisión no puede superar el 100%.',
        ];
    }
}
