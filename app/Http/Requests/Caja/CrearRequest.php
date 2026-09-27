<?php

namespace App\Http\Requests\Caja;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class CrearRequest extends BaseRequest
{
    public function rules(): array
    {
        $empresaId = $this->empresaId();

        return [
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('cajas', 'nombre')->where(fn ($query) => $query->where('empresa_id', $empresaId)),
            ],
            'codigo' => ['nullable', 'string', 'max:50'],
            'almacen_id' => [
                'nullable',
                'integer',
                Rule::exists('almacenes', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)),
            ],
            'descripcion' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la caja es obligatorio.',
            'nombre.unique' => 'Ya existe una caja registrada con este nombre en la empresa.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no debe superar los 100 caracteres.',
            'almacen_id.exists' => 'El almacén seleccionado no pertenece a la empresa.',
            'descripcion.max' => 'La descripción no debe superar los 500 caracteres.',
        ];
    }
}
