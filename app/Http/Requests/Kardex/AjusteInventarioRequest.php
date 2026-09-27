<?php

namespace App\Http\Requests\Kardex;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class AjusteInventarioRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'almacen_id' => [
                'required',
                'integer',
                Rule::exists('almacenes', 'id')->where(fn ($q) => $q->where('empresa_id', $this->empresaId())->where('estado', true)),
            ],
            'tipo_ajuste' => [
                'required',
                'string',
                'in:entrada,salida',
            ],
            'motivo' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'fecha' => [
                'nullable',
                'date',
            ],
            'detalles' => [
                'required',
                'array',
                'min:1',
            ],
            'detalles.*.producto_id' => [
                'required',
                'integer',
                Rule::exists('productos', 'id')->where(fn ($q) => $q->where('empresa_id', $this->empresaId())->where('estado', true)),
            ],
            'detalles.*.cantidad' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'detalles.*.costo_unitario_usd' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ];
    }

    /**
     * Custom validation messages in Spanish.
     */
    public function messages(): array
    {
        return [
            'almacen_id.required' => 'Debe seleccionar el almacén donde se aplicará el ajuste.',
            'almacen_id.exists' => 'El almacén seleccionado no es válido o no pertenece a la empresa activa.',
            'tipo_ajuste.required' => 'Debe indicar si el ajuste es de Entrada o de Salida.',
            'tipo_ajuste.in' => 'El tipo de ajuste debe ser Entrada o Salida.',
            'motivo.required' => 'El motivo o justificación del ajuste es obligatorio.',
            'motivo.min' => 'El motivo debe contener al menos 3 caracteres.',
            'motivo.max' => 'El motivo no puede superar los 255 caracteres.',
            'detalles.required' => 'Debe incluir al menos un producto en el ajuste.',
            'detalles.min' => 'Debe incluir al menos un producto en el ajuste.',
            'detalles.*.producto_id.required' => 'Debe especificar el producto en cada renglón.',
            'detalles.*.producto_id.exists' => 'Uno de los productos seleccionados no existe o no pertenece a esta empresa.',
            'detalles.*.cantidad.required' => 'La cantidad es obligatoria para todos los renglones.',
            'detalles.*.cantidad.gt' => 'La cantidad debe ser mayor a cero.',
        ];
    }
}
