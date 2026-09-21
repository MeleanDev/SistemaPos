<?php

namespace App\Http\Requests\RecepcionMoto;

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
            'detalles.*.almacen_id' => ['nullable', 'integer', 'exists:almacenes,id'],
            'detalles.*.referencia' => ['required', 'string', 'max:100'],
            'detalles.*.marca' => ['required', 'string', 'max:100'],
            'detalles.*.modelo' => ['required', 'string', 'max:100'],
            'detalles.*.anio' => ['required', 'string', 'max:10'],
            'detalles.*.color' => ['required', 'string', 'max:100'],
            'detalles.*.cilindrada' => ['required', 'string', 'max:50'],
            'detalles.*.cantidad' => ['required', 'integer', 'min:1'],
            'detalles.*.costo_unitario_usd' => ['required', 'numeric', 'min:0.0001'],
            'detalles.*.descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'detalles.*.aplica_iva' => ['nullable', 'boolean'],
            'detalles.*.iva_porcentaje' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.margen_detal' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.precio_detal_usd' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.margen_mayorista' => ['nullable', 'numeric', 'min:0'],
            'detalles.*.precio_mayorista_usd' => ['nullable', 'numeric', 'min:0'],

            'detalles.*.seriales' => ['required', 'array', 'min:1'],
            'detalles.*.seriales.*.numero_niv' => ['required', 'string', 'max:50'],
            'detalles.*.seriales.*.numero_chasis' => ['required', 'string', 'max:100'],
            'detalles.*.seriales.*.numero_motor' => ['required', 'string', 'max:100'],
            'detalles.*.seriales.*.certificado_origen' => ['required', 'string', 'max:100'],
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
            'detalles.required' => 'Debes registrar al menos un lote de motos.',
            'detalles.min' => 'Debes registrar al menos un lote de motos.',
            'detalles.*.marca.required' => 'La marca de la moto es obligatoria.',
            'detalles.*.modelo.required' => 'El modelo de la moto es obligatorio.',
            'detalles.*.costo_unitario_usd.required' => 'El costo unitario es obligatorio.',
            'detalles.*.costo_unitario_usd.min' => 'El costo unitario debe ser mayor a cero.',
            'detalles.*.seriales.required' => 'Debe registrar la matriz de seriales para cada unidad del lote.',
            'detalles.*.seriales.*.numero_niv.required' => 'El N.I.V. (Serial único) es obligatorio para cada moto.',
            'detalles.*.seriales.*.numero_chasis.required' => 'El número de chasis es obligatorio para cada moto.',
            'detalles.*.seriales.*.numero_motor.required' => 'El número de motor es obligatorio para cada moto.',
            'detalles.*.seriales.*.certificado_origen.required' => 'El certificado de origen es obligatorio para cada moto.',
        ];
    }
}
