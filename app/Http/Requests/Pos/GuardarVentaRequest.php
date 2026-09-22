<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class GuardarVentaRequest extends BaseRequest
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
        $empresaId = $this->empresaId();

        return [
            'cliente_id' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where(fn ($q) => $q->where('estado', true)),
            ],
            'almacen_id' => [
                'required',
                'integer',
                Rule::exists('almacenes', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
            ],
            'tipo_venta' => ['required', 'string', 'in:detal,mayor,mayorista'],
            'condicion_pago' => ['nullable', 'string', 'in:contado,credito'],
            'dias_credito' => ['nullable', 'integer', 'min:0'],
            'tasa_cambio' => ['required', 'numeric', 'min:0.0001'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer'],
            'items.*.tipo_item' => ['nullable', 'string', 'in:producto,moto,servicio'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
            'items.*.precio_unitario_usd' => ['required', 'numeric', 'min:0.0001'],
            'items.*.descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.almacen_id' => ['nullable', 'integer'],
            'pagos' => ['nullable', 'array'],
            'pagos.*.metodo_pago_id' => ['required_with:pagos', 'integer'],
            'pagos.*.monto' => ['required_with:pagos', 'numeric', 'min:0.0001'],
            'pagos.*.moneda' => ['nullable', 'string', 'max:10'],
            'pagos.*.tasa_cambio' => ['nullable', 'numeric', 'min:0.0001'],
            'pagos.*.referencia' => ['nullable', 'string', 'max:100'],
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
            'cliente_id.required' => 'El cliente es obligatorio.',
            'cliente_id.exists' => 'El cliente seleccionado no es válido o está inactivo.',
            'almacen_id.required' => 'El almacén de despacho es obligatorio.',
            'almacen_id.exists' => 'El almacén seleccionado no pertenece a la empresa o no está disponible.',
            'tipo_venta.required' => 'El tipo de venta es obligatorio.',
            'tasa_cambio.required' => 'La tasa de cambio es obligatoria.',
            'tasa_cambio.min' => 'La tasa de cambio debe ser mayor a 0.',
            'items.required' => 'Debe incluir al menos un producto o servicio en la venta.',
            'items.min' => 'Debe incluir al menos un producto o servicio en la venta.',
            'items.*.producto_id.required' => 'El identificador del ítem es requerido.',
            'items.*.cantidad.required' => 'La cantidad del ítem es requerida.',
            'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.precio_unitario_usd.required' => 'El precio del ítem es requerido.',
            'items.*.precio_unitario_usd.min' => 'El precio unitario debe ser mayor a 0.',
        ];
    }
}
