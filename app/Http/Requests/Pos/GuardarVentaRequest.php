<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;
use App\Models\CajaTurno;
use App\Models\Moto;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\Servicio;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarVentaRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $empresaId = $this->empresaId();

        if (! $user || $empresaId <= 0) {
            return;
        }

        $esAdmin = $user->hasAnyRole(['SuperAdmin', 'Admin']);

        if (! $esAdmin && ! $this->filled('caja_turno_id')) {
            $turnoPropio = $user->cajaTurnoActivo($empresaId);
            if ($turnoPropio) {
                $this->merge([
                    'caja_turno_id' => $turnoPropio->id,
                ]);
            }
        }
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
            'vendedor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendedores', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
            ],
            'venta_en_espera_id' => ['nullable', 'integer'],
            'caja_turno_id' => [
                'required',
                'integer',
                Rule::exists('caja_turnos', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', 'abierta')),
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
            'items.*.descuento_usd' => ['nullable', 'numeric', 'min:0'],
            'items.*.almacen_id' => [
                'nullable',
                'integer',
                Rule::exists('almacenes', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
            ],
            'items.*.variante_texto' => ['nullable', 'string', 'max:255'],
            'items.*.producto_serial_id' => [
                'nullable',
                'integer',
                Rule::exists('producto_seriales', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', 'disponible')),
            ],
            'pagos' => ['nullable', 'array'],
            'pagos.*.metodo_pago_id' => [
                'required_with:pagos',
                'integer',
                Rule::exists('metodos_pago', 'id')->where(fn ($q) => $q->where('estado', true)),
            ],
            'pagos.*.monto' => ['required_with:pagos', 'numeric', 'min:0.0001'],
            'pagos.*.moneda' => ['nullable', 'string', Rule::in(['USD', 'VES', 'BS'])],
            'pagos.*.tasa_cambio' => ['nullable', 'numeric', 'min:0.0001'],
            'pagos.*.referencia' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            $empresaId = $this->empresaId();

            if (! $user || $empresaId <= 0) {
                return;
            }

            $esAdmin = $user->hasAnyRole(['SuperAdmin', 'Admin', 'superadmin', 'admin', 'administrador']);
            $puedeDescontar = $esAdmin || $user->can('ventas.descuentos');

            if (! $esAdmin && $this->filled('caja_turno_id')) {
                $turnoPropio = $user->cajaTurnoActivo($empresaId);
                if (! $turnoPropio) {
                    $validator->errors()->add('caja_turno_id', 'No tienes una caja registradora aperturada asignada para procesar cobros.');
                } elseif ((int) $this->input('caja_turno_id') !== (int) $turnoPropio->id) {
                    $validator->errors()->add('caja_turno_id', 'Solo puedes facturar utilizando tu propio turno de caja abierto.');
                }
            }

            $tipoVenta = (string) $this->input('tipo_venta', 'detal');
            $esMayor = in_array($tipoVenta, ['mayor', 'mayorista'], true);
            $almacenGlobalId = (int) $this->input('almacen_id', 0);
            $items = $this->input('items', []);

            if (! is_array($items)) {
                return;
            }

            foreach ($items as $idx => $it) {
                if (! is_array($it)) {
                    continue;
                }

                $productoId = (int) ($it['producto_id'] ?? 0);
                $tipoItem = (string) ($it['tipo_item'] ?? 'producto');
                $cantidad = (float) ($it['cantidad'] ?? 0);
                $precioUnitarioUsd = (float) ($it['precio_unitario_usd'] ?? 0);
                $descuentoPorc = (float) ($it['descuento_porcentaje'] ?? 0);
                $descuentoInputUsd = isset($it['descuento_usd']) ? (float) $it['descuento_usd'] : 0.0;
                $almacenItem = ! empty($it['almacen_id']) ? (int) $it['almacen_id'] : $almacenGlobalId;
                $serialId = ! empty($it['producto_serial_id']) ? (int) $it['producto_serial_id'] : null;

                if ($productoId <= 0 || $cantidad <= 0 || $precioUnitarioUsd <= 0) {
                    continue;
                }

                $precioListaConIva = 0.0;
                $precioListaSinIva = 0.0;
                $costoUnitarioUsd = 0.0;
                $nombreItem = "Ítem #{$productoId}";

                if ($tipoItem === 'producto') {
                    $producto = Producto::where('id', $productoId)
                        ->where('empresa_id', $empresaId)
                        ->where('estado', true)
                        ->first();

                    if (! $producto) {
                        $validator->errors()->add("items.{$idx}.producto_id", 'El producto seleccionado no existe, no pertenece a la empresa o está inactivo.');

                        continue;
                    }

                    $nombreItem = $producto->nombre;
                    $costoUnitarioUsd = (float) $producto->precio_costo_usd;
                    $precioBase = $esMayor
                        ? (float) ($producto->precio_mayorista_usd > 0 ? $producto->precio_mayorista_usd : $producto->precio_detal_usd)
                        : (float) $producto->precio_detal_usd;
                    $aplicaIva = (bool) $producto->aplica_iva;
                    $ivaPorcentaje = $aplicaIva ? (float) ($producto->iva_porcentaje ?? 16.00) : 0.0;
                    $factorIva = ($aplicaIva && $ivaPorcentaje > 0) ? (1 + ($ivaPorcentaje / 100)) : 1.0;

                    $precioListaConIva = round($precioBase * $factorIva, 2);
                    $precioListaSinIva = round($precioBase, 2);

                    if ($producto->maneja_seriales && ! $serialId) {
                        $validator->errors()->add("items.{$idx}.producto_serial_id", "El producto '{$producto->nombre}' maneja seriales y requiere seleccionar un serial disponible.");
                    }

                    if ($serialId) {
                        $serialValido = ProductoSerial::where('id', $serialId)
                            ->where('empresa_id', $empresaId)
                            ->where('producto_id', $producto->id)
                            ->where('almacen_id', $almacenItem)
                            ->where('estado', 'disponible')
                            ->exists();

                        if (! $serialValido) {
                            $validator->errors()->add("items.{$idx}.producto_serial_id", "El serial seleccionado para '{$producto->nombre}' no pertenece al producto/almacén o no está disponible.");
                        }
                    }
                } elseif ($tipoItem === 'moto') {
                    $moto = Moto::where('id', $productoId)
                        ->where('empresa_id', $empresaId)
                        ->first();

                    if (! $moto || $moto->estado !== 'disponible') {
                        $validator->errors()->add("items.{$idx}.producto_id", 'La moto seleccionada no pertenece a la empresa o no se encuentra disponible.');

                        continue;
                    }

                    $nombreItem = "{$moto->marca} {$moto->modelo}";
                    $costoUnitarioUsd = (float) $moto->precio_costo_usd;
                    $ivaPorcentaje = (float) ($moto->iva_porcentaje ?? 16.00);
                    $factorIva = $ivaPorcentaje > 0 ? (1 + ($ivaPorcentaje / 100)) : 1.16;
                    $detalBase = (float) $moto->precio_detal_usd;
                    $mayorBase = (float) ($moto->precio_mayorista_usd > 0 ? $moto->precio_mayorista_usd : $moto->precio_detal_usd);
                    $detalConIva = (float) ($moto->precio_detal_con_iva_usd > 0 ? $moto->precio_detal_con_iva_usd : round($detalBase * $factorIva, 2));
                    $mayorConIva = (float) ($moto->precio_mayorista_con_iva_usd > 0 ? $moto->precio_mayorista_con_iva_usd : round($mayorBase * $factorIva, 2));

                    $precioListaConIva = round($esMayor ? $mayorConIva : $detalConIva, 2);
                    $precioListaSinIva = round($esMayor ? $mayorBase : $detalBase, 2);
                } elseif ($tipoItem === 'servicio') {
                    $servicio = Servicio::where('id', $productoId)
                        ->where('empresa_id', $empresaId)
                        ->where('estado', true)
                        ->first();

                    if (! $servicio) {
                        $validator->errors()->add("items.{$idx}.producto_id", 'El servicio seleccionado no pertenece a la empresa o está inactivo.');

                        continue;
                    }

                    $nombreItem = $servicio->nombre;
                    $costoUnitarioUsd = (float) $servicio->precio_costo_usd;
                    $precioBase = (float) $servicio->precio_venta_usd;
                    $aplicaIva = (bool) $servicio->aplica_iva;
                    $ivaPorcentaje = $aplicaIva ? (float) ($servicio->iva_porcentaje ?? 16.00) : 0.0;
                    $factorIva = ($aplicaIva && $ivaPorcentaje > 0) ? (1 + ($ivaPorcentaje / 100)) : 1.0;

                    $precioListaConIva = round($precioBase * $factorIva, 2);
                    $precioListaSinIva = round($precioBase, 2);
                }

                $difiereDeLista = abs($precioUnitarioUsd - $precioListaConIva) > 0.01
                    && abs($precioUnitarioUsd - $precioListaSinIva) > 0.01;
                $tieneDescuento = $descuentoPorc > 0 || $descuentoInputUsd > 0;

                if ($difiereDeLista && ! $puedeDescontar) {
                    $validator->errors()->add(
                        "items.{$idx}.precio_unitario_usd",
                        "El precio unitario de '{$nombreItem}' no coincide con el precio de lista oficial y no tienes permiso para modificar precios o aplicar descuentos (ventas.descuentos)."
                    );
                }

                if ($tieneDescuento && ! $puedeDescontar) {
                    $validator->errors()->add(
                        "items.{$idx}.descuento_porcentaje",
                        "No tienes permiso para aplicar descuentos en '{$nombreItem}' (se requiere el permiso ventas.descuentos)."
                    );
                }

                $renglonBrutoUsd = round($cantidad * $precioUnitarioUsd, 2);
                if ($descuentoInputUsd > $renglonBrutoUsd + 0.01) {
                    $validator->errors()->add(
                        "items.{$idx}.descuento_usd",
                        "El descuento en '{$nombreItem}' no puede superar el 100% del monto del renglón."
                    );
                }

                $descuentoAplicadoUsd = $descuentoInputUsd > 0
                    ? min($renglonBrutoUsd, round($descuentoInputUsd, 2))
                    : ($descuentoPorc > 0 ? round($renglonBrutoUsd * ($descuentoPorc / 100), 2) : 0.0);
                $renglonNetoUsd = round($renglonBrutoUsd - $descuentoAplicadoUsd, 2);
                $precioNetoUnitarioUsd = $cantidad > 0 ? round($renglonNetoUsd / $cantidad, 4) : 0.0;

                if ($costoUnitarioUsd > 0 && ($precioUnitarioUsd + 0.0001 < $costoUnitarioUsd || $precioNetoUnitarioUsd + 0.0001 < $costoUnitarioUsd)) {
                    $validator->errors()->add(
                        "items.{$idx}.precio_unitario_usd",
                        "El precio de venta neto de '{$nombreItem}' ($".number_format($precioNetoUnitarioUsd, 2).') no puede ser menor al costo ($'.number_format($costoUnitarioUsd, 2).').'
                    );
                }
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $empresaId = $this->empresaId();
        $user = $this->user();
        $esAdmin = $user ? $user->hasAnyRole(['SuperAdmin', 'Admin']) : false;

        $mensajeCajaTurnoRequired = 'No tienes una caja registradora aperturada asignada para procesar cobros.';
        if ($esAdmin) {
            $hayAbiertas = $empresaId > 0 && CajaTurno::where('empresa_id', $empresaId)->where('estado', 'abierta')->exists();
            $mensajeCajaTurnoRequired = $hayAbiertas
                ? 'Debes seleccionar una caja con turno abierto para procesar la venta.'
                : 'No hay cajas abiertas para procesar la venta.';
        }

        return [
            'cliente_id.required' => 'El cliente es obligatorio.',
            'cliente_id.exists' => 'El cliente seleccionado no es válido o está inactivo.',
            'almacen_id.required' => 'El almacén de despacho es obligatorio.',
            'almacen_id.exists' => 'El almacén seleccionado no pertenece a la empresa o no está disponible.',
            'caja_turno_id.required' => $mensajeCajaTurnoRequired,
            'caja_turno_id.exists' => 'El turno de caja seleccionado no pertenece a la empresa o no se encuentra abierto.',
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
            'items.*.descuento_porcentaje.min' => 'El porcentaje de descuento no puede ser negativo.',
            'items.*.descuento_porcentaje.max' => 'El porcentaje de descuento no puede superar el 100%.',
            'items.*.descuento_usd.min' => 'El monto de descuento no puede ser negativo.',
            'items.*.almacen_id.exists' => 'El almacén indicado para el ítem no pertenece a la empresa o está inactivo.',
            'items.*.producto_serial_id.exists' => 'El serial seleccionado no pertenece a la empresa o no está disponible.',
            'pagos.*.metodo_pago_id.required_with' => 'El método de pago es obligatorio.',
            'pagos.*.metodo_pago_id.exists' => 'El método de pago seleccionado no es válido o se encuentra inactivo.',
            'pagos.*.moneda.in' => 'La moneda del pago debe ser USD, VES o BS.',
        ];
    }
}
