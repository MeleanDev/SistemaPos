<?php

namespace App\Http\Requests\Recepcion;

use Illuminate\Foundation\Http\FormRequest;

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
     */
    public function rules(): array
    {
        return [
            'almacen_id' => ['required', 'integer', 'exists:almacenes,id'],
            'proveedor_id' => ['required', 'integer', 'exists:proveedores,id'],
            'tipo_documento' => ['required', 'string', 'in:factura,nota_entrega,guia_despacho,orden_compra'],
            'numero_documento' => ['required', 'string', 'max:100'],
            'numero_control' => ['nullable', 'string', 'max:100'],
            'moneda_documento' => ['nullable', 'string', 'in:USD,VES'],
            'fecha_emision' => ['required', 'date'],
            'fecha_recepcion' => ['required', 'date'],
            'condicion_pago' => ['required', 'string', 'in:contado,credito'],
            'dias_credito' => ['nullable', 'integer', 'min:0', 'max:365'],
            'tasa_cambio' => ['nullable', 'numeric', 'min:0.0001'],
            'monto_bruto_usd' => ['nullable', 'numeric', 'min:0'],
            'descuento_global_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'detalles.*.almacen_id' => ['nullable', 'integer', 'exists:almacenes,id'],
            'detalles.*.cantidad' => ['required', 'numeric', 'min:0.001'],
            'detalles.*.bultos' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.unidades_por_bulto' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.costo_bulto_usd' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.costo_unitario_usd' => ['required', 'numeric', 'min:0.0001'],
            'detalles.*.descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'detalles.*.aplica_iva' => ['nullable', 'boolean'],
            'detalles.*.iva_porcentaje' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.margen_detal_porcentaje' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.precio_detal_usd' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.margen_mayorista_porcentaje' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.precio_mayorista_usd' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'almacen_id.required' => 'Debes seleccionar el almacén de destino.',
            'almacen_id.exists' => 'El almacén seleccionado no es válido.',
            'proveedor_id.required' => 'Debes seleccionar el proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
            'numero_documento.required' => 'El número de factura / documento del proveedor es obligatorio.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
            'fecha_recepcion.required' => 'La fecha de recepción es obligatoria.',
            'detalles.required' => 'Debes agregar al menos un producto a la recepción.',
            'detalles.min' => 'Debes agregar al menos un producto a la recepción.',
            'detalles.*.producto_id.required' => 'Cada renglón debe tener un producto válido.',
            'detalles.*.cantidad.required' => 'La cantidad recibida es obligatoria.',
            'detalles.*.cantidad.min' => 'La cantidad debe ser mayor a cero.',
            'detalles.*.costo_unitario_usd.required' => 'El costo unitario es obligatorio.',
            'detalles.*.costo_unitario_usd.min' => 'El costo unitario debe ser mayor a cero.',
        ];
    }
}
