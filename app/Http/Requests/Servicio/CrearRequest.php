<?php

namespace App\Http\Requests\Servicio;

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
            'categoria_id' => [
                'required',
                'integer',
                Rule::exists('categorias', 'id')->where(fn ($q) => $q->where('empresa_id', $this->empresaId())->where('estado', true)),
            ],
            'codigo' => [
                'required',
                'string',
                'min:2',
                'max:50',
                Rule::unique('servicios', 'codigo')->where(fn ($q) => $q->where('empresa_id', $this->empresaId())->where('estado', true)),
            ],
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:150',
                Rule::unique('servicios', 'nombre')->where(fn ($q) => $q->where('empresa_id', $this->empresaId())->where('estado', true)),
            ],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'precio_venta_usd' => ['required', 'numeric', 'min:0'],
            'precio_venta_bs' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'categoria_id.required' => 'Debe seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no existe o está inactiva.',
            'codigo.required' => 'El código del servicio es obligatorio.',
            'codigo.unique' => 'Ya existe un servicio con este código en tu empresa.',
            'nombre.required' => 'El nombre del servicio es obligatorio.',
            'nombre.unique' => 'Ya existe un servicio con este nombre en tu empresa.',
            'precio_venta_usd.required' => 'El precio de venta en USD es obligatorio.',
            'precio_venta_usd.min' => 'El precio de venta en USD no puede ser negativo.',
        ];
    }
}
