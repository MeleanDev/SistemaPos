<?php

namespace App\Http\Requests\Kardex;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class TrasladoInventarioRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'almacen_origen_id' => [
                'required',
                'integer',
                Rule::exists('almacenes', 'id')->where(fn ($q) => $q->where('empresa_id', $this->empresaId())->where('estado', true)),
            ],
            'almacen_destino_id' => [
                'required',
                'integer',
                'different:almacen_origen_id',
                Rule::exists('almacenes', 'id')->where(fn ($q) => $q->where('empresa_id', $this->empresaId())->where('estado', true)),
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
        ];
    }

    /**
     * Custom validation messages in Spanish.
     */
    public function messages(): array
    {
        return [
            'almacen_origen_id.required' => 'Debe seleccionar el almacén de origen.',
            'almacen_origen_id.exists' => 'El almacén de origen no es válido o no pertenece a la empresa.',
            'almacen_destino_id.required' => 'Debe seleccionar el almacén de destino.',
            'almacen_destino_id.different' => 'El almacén de destino debe ser diferente al almacén de origen.',
            'almacen_destino_id.exists' => 'El almacén de destino no es válido o no pertenece a la empresa.',
            'motivo.required' => 'El motivo o justificación del traslado es obligatorio.',
            'motivo.min' => 'El motivo debe contener al menos 3 caracteres.',
            'motivo.max' => 'El motivo no puede superar los 255 caracteres.',
            'detalles.required' => 'Debe incluir al menos un producto para trasladar.',
            'detalles.min' => 'Debe incluir al menos un producto para trasladar.',
            'detalles.*.producto_id.required' => 'Debe especificar el producto en cada renglón.',
            'detalles.*.producto_id.exists' => 'Uno de los productos seleccionados no existe o no pertenece a esta empresa.',
            'detalles.*.cantidad.required' => 'La cantidad a trasladar es obligatoria para todos los renglones.',
            'detalles.*.cantidad.gt' => 'La cantidad a trasladar debe ser mayor a cero.',
        ];
    }
}
