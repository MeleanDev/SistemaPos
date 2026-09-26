<?php

namespace App\Http\Requests\RecepcionMoto;

use App\Http\Requests\BaseRequest;

class CrearRequest extends BaseRequest
{
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
            'tasa_compra' => ['nullable', 'numeric', 'min:0.0001'],
            'tasa_venta' => ['nullable', 'numeric', 'min:0.0001'],
            'monto_bruto_usd' => ['nullable', 'numeric', 'min:0'],
            'descuento_global_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'flete_total_usd' => ['nullable', 'numeric', 'min:0'],
            'incluir_flete_en_factura' => ['nullable', 'boolean'],
            'observaciones' => ['nullable', 'string', 'max:1000'],

            'detalles' => ['required', 'array', 'min:1'],
            'detalles.*.tipo_item' => ['nullable', 'string', 'in:moto,producto'],
            'detalles.*.producto_id' => ['nullable', 'integer', 'exists:productos,id'],
            'detalles.*.almacen_id' => ['nullable', 'integer', 'exists:almacenes,id'],
            'detalles.*.referencia' => ['nullable', 'string', 'max:100'],
            'detalles.*.marca' => ['nullable', 'string', 'max:100'],
            'detalles.*.modelo' => ['nullable', 'string', 'max:100'],
            'detalles.*.anio' => ['nullable', 'string', 'max:10'],
            'detalles.*.color' => ['nullable', 'string', 'max:100'],
            'detalles.*.cilindrada' => ['nullable', 'string', 'max:50'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.costo_unitario_usd' => ['required', 'numeric', 'min:0.0001'],
            'detalles.*.flete_unitario_usd' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'detalles.*.aplica_iva' => ['nullable', 'boolean'],
            'detalles.*.iva_porcentaje' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.margen_detal' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.precio_detal_usd' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.precio_detal_con_iva_usd' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.margen_mayorista' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.precio_mayorista_usd' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.precio_mayorista_con_iva_usd' => ['nullable', 'numeric', 'min:0'],

            'detalles.*.seriales' => ['nullable', 'array'],
            'detalles.*.seriales.*.numero_niv' => ['nullable', 'string', 'max:50'],
            'detalles.*.seriales.*.numero_chasis' => ['nullable', 'string', 'max:100'],
            'detalles.*.seriales.*.numero_motor' => ['nullable', 'string', 'max:100'],
            'detalles.*.seriales.*.certificado_origen' => ['nullable', 'string', 'max:100'],
            'detalles.*.seriales.*.placa' => ['nullable', 'string', 'max:50'],
            'detalles.*.seriales.*.almacen_id' => ['nullable', 'integer', 'exists:almacenes,id'],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'almacen_id.required' => 'Debes seleccionar el almacén general.',
            'almacen_id.exists' => 'El almacén general seleccionado no es válido.',
            'proveedor_id.required' => 'Debes seleccionar el proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no es válido.',
            'numero_documento.required' => 'El número de factura / documento es obligatorio.',
            'fecha_emision.required' => 'La fecha de emisión es obligatoria.',
            'fecha_recepcion.required' => 'La fecha de recepción es obligatoria.',
            'detalles.required' => 'Debes registrar al menos un ítem (moto o producto) en la recepción.',
            'detalles.min' => 'Debes registrar al menos un ítem en la recepción.',
            'detalles.*.costo_unitario_usd.required' => 'El costo unitario es obligatorio.',
            'detalles.*.costo_unitario_usd.min' => 'El costo unitario debe ser mayor a cero.',
        ];
    }
}
