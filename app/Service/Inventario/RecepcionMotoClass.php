<?php

namespace App\Service\Inventario;

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\CuentaPorPagar;
use App\Models\EmpresaMoneda;
use App\Models\Moto;
use App\Models\Producto;
use App\Models\ProductoProveedor;
use App\Models\Proveedor;
use App\Models\RecepcionMoto;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecepcionMotoClass
{
    public function __construct(
        protected KardexClass $kardexService
    ) {}

    /**
     * Listado de recepciones de motos por empresa
     */
    public function lista(int $empresaId)
    {
        return RecepcionMoto::with(['proveedor', 'almacen', 'usuario', 'detalles.producto'])
            ->where('empresa_id', $empresaId)
            ->orderBy('id', 'desc');
    }

    /**
     * Obtener detalle completo de una recepción de motos
     */
    public function detalle(int $id, int $empresaId)
    {
        return RecepcionMoto::with([
            'proveedor',
            'almacen',
            'usuario',
            'detalles.almacen',
            'detalles.producto',
            'detalles.motos.almacen',
            'motos.almacen',
        ])
            ->where('empresa_id', $empresaId)
            ->findOrFail($id);
    }

    /**
     * Catálogos para el formulario de recepción de motos y productos
     */
    public function catalogos(int $empresaId): array
    {
        $proveedores = Proveedor::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->select('id', 'rif', 'nombre', 'razon_social', 'telefono', 'correo')
            ->orderBy('nombre')
            ->get();

        $almacenes = Almacen::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->select('id', 'nombre', 'codigo', 'direccion')
            ->orderBy('nombre')
            ->get();

        $categorias = Categoria::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        $tasaOficial = $this->obtenerTasaOficial($empresaId);
        $codigoSugerido = $this->generarCodigo($empresaId);
        $proximaReferencia = $this->generarReferenciaNumerica($empresaId);

        $productos = Producto::with(['codigosBarra', 'stockAlmacenes.almacen', 'categoria'])
            ->where('empresa_id', $empresaId)
            ->where('tipo', 'producto')
            ->where('estado', true)
            ->orderBy('nombre')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'codigo_interno' => $p->codigo_interno,
                    'nombre' => $p->nombre,
                    'unidad_medida' => $p->unidad_medida,
                    'categoria_nombre' => $p->categoria?->nombre ?? 'General',
                    'precio_costo_usd' => (float) $p->precio_costo_usd,
                    'precio_costo_bs' => (float) $p->precio_costo_bs,
                    'precio_detal_usd' => (float) $p->precio_detal_usd,
                    'precio_detal_bs' => (float) $p->precio_detal_bs,
                    'precio_mayorista_usd' => (float) $p->precio_mayorista_usd,
                    'precio_mayorista_bs' => (float) $p->precio_mayorista_bs,
                    'ultimo_margen_detal' => (float) ($p->ultimo_margen_detal ?: 30.00),
                    'ultimo_margen_mayorista' => (float) ($p->ultimo_margen_mayorista ?: 15.00),
                    'aplica_iva' => (bool) $p->aplica_iva,
                    'iva_porcentaje' => (float) $p->iva_porcentaje,
                    'codigos_barra' => $p->codigosBarra->pluck('codigo_barra')->toArray(),
                    'stock_almacenes' => $p->stockAlmacenes->map(function ($stk) {
                        return [
                            'almacen_id' => $stk->almacen_id,
                            'almacen_nombre' => $stk->almacen?->nombre,
                            'cantidad_actual' => (float) $stk->cantidad_actual,
                        ];
                    }),
                ];
            });

        return [
            'proveedores' => $proveedores,
            'almacenes' => $almacenes,
            'categorias' => $categorias,
            'productos' => $productos,
            'tasa_oficial' => $tasaOficial,
            'tasa_compra' => $tasaOficial,
            'tasa_venta' => $tasaOficial,
            'codigo_sugerido' => $codigoSugerido,
            'proxima_referencia' => $proximaReferencia,
        ];
    }

    /**
     * Generar correlativo único de recepción de motos por empresa
     */
    public function generarCodigo(int $empresaId): string
    {
        $recepciones = RecepcionMoto::where('empresa_id', $empresaId)->get(['id', 'codigo']);
        $maxNum = 0;

        foreach ($recepciones as $r) {
            $cod = trim($r->codigo ?? '');
            if (preg_match('/(\d+)/', $cod, $matches)) {
                $val = (int) $matches[1];
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            }
        }

        $numero = str_pad($maxNum + 1, 6, '0', STR_PAD_LEFT);

        return 'RECMOTO-'.$numero;
    }

    /**
     * Generar referencia numérica autoincrementable para motos por empresa
     */
    public function generarReferenciaNumerica(int $empresaId): string
    {
        $motos = Moto::where('empresa_id', $empresaId)->get(['id', 'referencia']);
        $maxNum = 0;

        foreach ($motos as $m) {
            $ref = trim($m->referencia ?? '');
            if (is_numeric($ref)) {
                $val = (int) $ref;
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            } elseif (preg_match('/(\d+)/', $ref, $matches)) {
                $val = (int) $matches[1];
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            }
        }

        return (string) ($maxNum + 1);
    }

    /**
     * Obtener tasa de cambio oficial de la empresa
     */
    public function obtenerTasaOficial(int $empresaId): float
    {
        $moneda = EmpresaMoneda::where('empresa_id', $empresaId)
            ->where('codigo', 'USD')
            ->first();

        return $moneda && $moneda->tasa_cambio > 0 ? (float) $moneda->tasa_cambio : 1.0000;
    }

    /**
     * Guardar recepción mixta de lote de motos y productos
     */
    public function guardar(array $datos, $user, int $empresaId): RecepcionMoto
    {
        return DB::transaction(function () use ($datos, $user, $empresaId) {
            $tasaOficial = $this->obtenerTasaOficial($empresaId);
            $tasaCompra = ! empty($datos['tasa_compra']) && $datos['tasa_compra'] > 0
                ? (float) $datos['tasa_compra']
                : (! empty($datos['tasa_cambio']) && $datos['tasa_cambio'] > 0 ? (float) $datos['tasa_cambio'] : $tasaOficial);

            $tasaVenta = ! empty($datos['tasa_venta']) && $datos['tasa_venta'] > 0
                ? (float) $datos['tasa_venta']
                : (! empty($datos['tasa_cambio']) && $datos['tasa_cambio'] > 0 ? (float) $datos['tasa_cambio'] : $tasaOficial);

            $tasaCambio = $tasaVenta;
            $monedaDoc = $datos['moneda_documento'] ?? 'USD';
            $esVes = ($monedaDoc === 'VES');
            $tasaMenor = ($tasaCompra < $tasaVenta);
            $incluirFlete = ! empty($datos['incluir_flete_en_factura']);

            $nivsEnviados = [];
            $chasisEnviados = [];
            $motoresEnviados = [];

            // 1. Validar seriales únicos de las motos
            foreach ($datos['detalles'] as $idxDetalle => $det) {
                $tipoItem = $det['tipo_item'] ?? 'moto';

                if ($tipoItem === 'moto') {
                    if (empty($det['seriales']) || ! is_array($det['seriales'])) {
                        throw ValidationException::withMessages([
                            "detalles.{$idxDetalle}.seriales" => "Debe ingresar los seriales para el modelo {$det['marca']} {$det['modelo']}.",
                        ]);
                    }

                    if (count($det['seriales']) !== (int) $det['cantidad']) {
                        throw ValidationException::withMessages([
                            "detalles.{$idxDetalle}.seriales" => 'La cantidad de seriales ('.count($det['seriales']).") no coincide con la cantidad del lote ({$det['cantidad']}) en {$det['marca']} {$det['modelo']}.",
                        ]);
                    }

                    foreach ($det['seriales'] as $sIdx => $serial) {
                        $niv = strtoupper(trim($serial['numero_niv'] ?? ''));
                        $chasis = strtoupper(trim($serial['numero_chasis'] ?? ''));
                        $motor = strtoupper(trim($serial['numero_motor'] ?? ''));

                        if (empty($niv) || empty($chasis) || empty($motor)) {
                            throw ValidationException::withMessages([
                                'detalles' => 'Faltan seriales obligatorios en el renglón #'.($idxDetalle + 1).', unidad #'.($sIdx + 1).'.',
                            ]);
                        }

                        if (in_array($niv, $nivsEnviados, true)) {
                            throw ValidationException::withMessages([
                                'seriales' => "El N.I.V. '{$niv}' está duplicado en este mismo documento.",
                            ]);
                        }
                        if (in_array($chasis, $chasisEnviados, true)) {
                            throw ValidationException::withMessages([
                                'seriales' => "El Número de Chasis '{$chasis}' está duplicado en este mismo documento.",
                            ]);
                        }
                        if (in_array($motor, $motoresEnviados, true)) {
                            throw ValidationException::withMessages([
                                'seriales' => "El Número de Motor '{$motor}' está duplicado en este mismo documento.",
                            ]);
                        }

                        $nivsEnviados[] = $niv;
                        $chasisEnviados[] = $chasis;
                        $motoresEnviados[] = $motor;

                        $duplicadoNiv = Moto::where('empresa_id', $empresaId)
                            ->where('numero_niv', $niv)
                            ->whereIn('estado', ['disponible', 'reservada'])
                            ->first();
                        if ($duplicadoNiv) {
                            throw ValidationException::withMessages([
                                'seriales' => "El N.I.V. '{$niv}' ya se encuentra registrado en el inventario activo de esta empresa.",
                            ]);
                        }

                        $duplicadoChasis = Moto::where('empresa_id', $empresaId)
                            ->where('numero_chasis', $chasis)
                            ->whereIn('estado', ['disponible', 'reservada'])
                            ->first();
                        if ($duplicadoChasis) {
                            throw ValidationException::withMessages([
                                'seriales' => "El Número de Chasis '{$chasis}' ya se encuentra registrado en el inventario activo.",
                            ]);
                        }

                        $duplicadoMotor = Moto::where('empresa_id', $empresaId)
                            ->where('numero_motor', $motor)
                            ->whereIn('estado', ['disponible', 'reservada'])
                            ->first();
                        if ($duplicadoMotor) {
                            throw ValidationException::withMessages([
                                'seriales' => "El Número de Motor '{$motor}' ya se encuentra registrado en el inventario activo.",
                            ]);
                        }
                    }
                } elseif ($tipoItem === 'producto') {
                    if (empty($det['producto_id'])) {
                        throw ValidationException::withMessages([
                            "detalles.{$idxDetalle}.producto_id" => 'Debe seleccionar un producto válido para el renglón #'.($idxDetalle + 1).'.',
                        ]);
                    }
                }
            }

            // 2. Cálculos fiscales detallados por renglón
            $baseImponibleUsd = 0;
            $exentoUsd = 0;
            $descuentoRenglonesUsd = 0;
            $ivaGlobalUsd = 0;
            $fleteGlobalUsd = 0;
            $totalUnidades = 0;
            $detallesCalculados = [];
            $ivaPorcentajeGeneral = 16.00;

            foreach ($datos['detalles'] as $det) {
                $tipoItem = $det['tipo_item'] ?? 'moto';
                $cantidad = (int) $det['cantidad'];
                $totalUnidades += $cantidad;

                $costoUnitarioUsd = (float) $det['costo_unitario_usd'];
                if (! $esVes) {
                    $costoUnitarioBs = $tasaMenor ? round($costoUnitarioUsd * $tasaVenta, 4) : round($costoUnitarioUsd * $tasaCompra, 4);
                } else {
                    $costoUnitarioBs = $tasaMenor ? round($costoUnitarioUsd * $tasaCompra, 4) : round($costoUnitarioUsd * $tasaVenta, 4);
                }

                $fleteUnitarioUsd = ! empty($det['flete_unitario_usd']) ? (float) $det['flete_unitario_usd'] : 0;
                $fleteUnitarioBs = round($fleteUnitarioUsd * $tasaCompra, 4);
                $fleteGlobalUsd += ($fleteUnitarioUsd * $cantidad);

                $descPct = ! empty($det['descuento_porcentaje']) ? (float) $det['descuento_porcentaje'] : 0;
                $descUsd = round($costoUnitarioUsd * ($descPct / 100), 4);
                $descBs = round($descUsd * $tasaCompra, 4);
                $descuentoRenglonesUsd += ($descUsd * $cantidad);

                $costoNetoUsd = $costoUnitarioUsd - $descUsd;
                $costoNetoBs = round($costoNetoUsd * $tasaCompra, 4);

                $aplicaIva = ! empty($det['aplica_iva']);
                $ivaPct = $aplicaIva ? (! empty($det['iva_porcentaje']) ? (float) $det['iva_porcentaje'] : 16.00) : 0;
                if ($aplicaIva && $ivaPct > 0) {
                    $ivaPorcentajeGeneral = $ivaPct;
                }

                $ivaUnitarioUsd = $aplicaIva ? round($costoNetoUsd * ($ivaPct / 100), 4) : 0;
                $ivaUnitarioBs = round($ivaUnitarioUsd * $tasaCompra, 4);

                // Costo Total = Costo Base Neto + IVA de Compra + Flete Unitario
                $costoTotalUnitarioUsd = round($costoNetoUsd + $ivaUnitarioUsd + $fleteUnitarioUsd, 4);
                $costoTotalUnitarioBs = round($costoTotalUnitarioUsd * ($tasaMenor ? $tasaVenta : $tasaCompra), 4);

                $margenDetalPct = ! empty($det['margen_detal']) ? (float) $det['margen_detal'] : 0;
                if (! $esVes) {
                    $sugeridoDetalConIvaUsd = $tasaMenor
                        ? ($costoTotalUnitarioUsd * (1 + ($margenDetalPct / 100)))
                        : (($costoTotalUnitarioUsd * (1 + ($margenDetalPct / 100)) * $tasaCompra) / $tasaVenta);
                } else {
                    $sugeridoDetalConIvaUsd = $costoTotalUnitarioUsd * (1 + ($margenDetalPct / 100));
                }

                $factorIva = ($aplicaIva && $ivaPct > 0) ? (1 + ($ivaPct / 100)) : 1;
                $sugeridoDetalSinIvaUsd = $sugeridoDetalConIvaUsd / $factorIva;

                $precioDetalUsd = ! empty($det['precio_detal_usd']) && $det['precio_detal_usd'] > 0
                    ? (float) $det['precio_detal_usd']
                    : round($sugeridoDetalSinIvaUsd, 4);
                $precioDetalBs = round($precioDetalUsd * $tasaVenta, 4);

                $precioDetalConIvaUsd = ! empty($det['precio_detal_con_iva_usd']) && $det['precio_detal_con_iva_usd'] > 0
                    ? (float) $det['precio_detal_con_iva_usd']
                    : round($precioDetalUsd * $factorIva, 4);
                $precioDetalConIvaBs = round($precioDetalConIvaUsd * $tasaVenta, 4);

                $margenMayoristaPct = ! empty($det['margen_mayorista']) ? (float) $det['margen_mayorista'] : 0;
                if (! $esVes) {
                    $sugeridoMayoristaConIvaUsd = $tasaMenor
                        ? ($costoTotalUnitarioUsd * (1 + ($margenMayoristaPct / 100)))
                        : (($costoTotalUnitarioUsd * (1 + ($margenMayoristaPct / 100)) * $tasaCompra) / $tasaVenta);
                } else {
                    $sugeridoMayoristaConIvaUsd = $costoTotalUnitarioUsd * (1 + ($margenMayoristaPct / 100));
                }

                $sugeridoMayoristaSinIvaUsd = $sugeridoMayoristaConIvaUsd / $factorIva;

                $precioMayoristaUsd = ! empty($det['precio_mayorista_usd']) && $det['precio_mayorista_usd'] > 0
                    ? (float) $det['precio_mayorista_usd']
                    : round($sugeridoMayoristaSinIvaUsd, 4);
                $precioMayoristaBs = round($precioMayoristaUsd * $tasaVenta, 4);

                $precioMayoristaConIvaUsd = ! empty($det['precio_mayorista_con_iva_usd']) && $det['precio_mayorista_con_iva_usd'] > 0
                    ? (float) $det['precio_mayorista_con_iva_usd']
                    : round($precioMayoristaUsd * $factorIva, 4);
                $precioMayoristaConIvaBs = round($precioMayoristaConIvaUsd * $tasaVenta, 4);

                $renglonSubtotalUsd = round($costoNetoUsd * $cantidad, 2);
                $renglonSubtotalBs = round($renglonSubtotalUsd * $tasaCompra, 2);

                $renglonIvaUsd = round($ivaUnitarioUsd * $cantidad, 2);
                $renglonIvaBs = round($renglonIvaUsd * $tasaCompra, 2);

                $renglonTotalUsd = round($renglonSubtotalUsd + $renglonIvaUsd + ($incluirFlete ? ($fleteUnitarioUsd * $cantidad) : 0), 2);
                $renglonTotalBs = round($renglonTotalUsd * $tasaCompra, 2);

                if ($aplicaIva && $ivaPct > 0) {
                    $baseImponibleUsd += $renglonSubtotalUsd;
                    $ivaGlobalUsd += $renglonIvaUsd;
                } else {
                    $exentoUsd += $renglonSubtotalUsd;
                }

                $refLote = trim($det['referencia'] ?? '');
                if (empty($refLote)) {
                    $refLote = $this->generarReferenciaNumerica($empresaId);
                }

                $detallesCalculados[] = [
                    'tipo_item' => $tipoItem,
                    'producto_id' => ! empty($det['producto_id']) ? (int) $det['producto_id'] : null,
                    'almacen_id' => $det['almacen_id'] ?? $datos['almacen_id'],
                    'referencia' => $refLote,
                    'marca' => ! empty($det['marca']) ? trim($det['marca']) : 'N/A',
                    'modelo' => ! empty($det['modelo']) ? trim($det['modelo']) : 'N/A',
                    'anio' => ! empty($det['anio']) ? trim($det['anio']) : date('Y'),
                    'color' => ! empty($det['color']) ? trim($det['color']) : 'N/A',
                    'cilindrada' => ! empty($det['cilindrada']) ? trim($det['cilindrada']) : 'N/A',
                    'cantidad' => $cantidad,
                    'costo_unitario_usd' => $costoUnitarioUsd,
                    'costo_unitario_bs' => $costoUnitarioBs,
                    'flete_unitario_usd' => $fleteUnitarioUsd,
                    'flete_unitario_bs' => $fleteUnitarioBs,
                    'costo_total_unitario_usd' => $costoTotalUnitarioUsd,
                    'costo_total_unitario_bs' => $costoTotalUnitarioBs,
                    'descuento_porcentaje' => $descPct,
                    'descuento_usd' => $descUsd,
                    'descuento_bs' => $descBs,
                    'aplica_iva' => $aplicaIva,
                    'iva_porcentaje' => $ivaPct,
                    'iva_monto_usd' => $ivaUnitarioUsd,
                    'iva_monto_bs' => $ivaUnitarioBs,
                    'margen_detal' => $margenDetalPct,
                    'precio_detal_usd' => $precioDetalUsd,
                    'precio_detal_bs' => $precioDetalBs,
                    'precio_detal_con_iva_usd' => $precioDetalConIvaUsd,
                    'precio_detal_con_iva_bs' => $precioDetalConIvaBs,
                    'margen_mayorista' => $margenMayoristaPct,
                    'precio_mayorista_usd' => $precioMayoristaUsd,
                    'precio_mayorista_bs' => $precioMayoristaBs,
                    'precio_mayorista_con_iva_usd' => $precioMayoristaConIvaUsd,
                    'precio_mayorista_con_iva_bs' => $precioMayoristaConIvaBs,
                    'subtotal_usd' => $renglonSubtotalUsd,
                    'subtotal_bs' => $renglonSubtotalBs,
                    'total_usd' => $renglonTotalUsd,
                    'total_bs' => $renglonTotalBs,
                    'seriales' => $det['seriales'] ?? [],
                ];
            }

            // Descuento global
            $descGlobalPct = ! empty($datos['descuento_global_porcentaje']) ? (float) $datos['descuento_global_porcentaje'] : 0;
            $subtotalNetoAntesDescGlobal = $baseImponibleUsd + $exentoUsd;
            $descGlobalUsd = round($subtotalNetoAntesDescGlobal * ($descGlobalPct / 100), 2);
            $descGlobalBs = round($descGlobalUsd * $tasaCompra, 2);

            if ($descGlobalPct > 0) {
                $baseImponibleUsd = round($baseImponibleUsd * (1 - ($descGlobalPct / 100)), 2);
                $exentoUsd = round($exentoUsd * (1 - ($descGlobalPct / 100)), 2);
                $ivaGlobalUsd = round($baseImponibleUsd * ($ivaPorcentajeGeneral / 100), 2);
            }

            $subtotalGlobalUsd = round($baseImponibleUsd + $exentoUsd, 2);
            $subtotalBs = round($subtotalGlobalUsd * $tasaCompra, 2);
            $baseImponibleBs = round($baseImponibleUsd * $tasaCompra, 2);
            $exentoBs = round($exentoUsd * $tasaCompra, 2);
            $ivaBs = round($ivaGlobalUsd * $tasaCompra, 2);
            $fleteGlobalBs = round($fleteGlobalUsd * $tasaCompra, 2);

            $montoBrutoUsd = ! empty($datos['monto_bruto_usd']) ? (float) $datos['monto_bruto_usd'] : ($subtotalGlobalUsd + $descuentoRenglonesUsd + $descGlobalUsd);
            $montoBrutoBs = round($montoBrutoUsd * $tasaCompra, 2);

            $totalGlobalUsd = round($subtotalGlobalUsd + $ivaGlobalUsd + ($incluirFlete ? $fleteGlobalUsd : 0), 2);
            $totalBs = round($totalGlobalUsd * $tasaCompra, 2);

            // 3. Crear cabecera RecepcionMoto
            $diasCredito = ($datos['condicion_pago'] === 'credito') ? (int) ($datos['dias_credito'] ?? 30) : 0;
            $fechaVencimiento = ($datos['condicion_pago'] === 'credito')
                ? Carbon::parse($datos['fecha_emision'])->addDays($diasCredito)->toDateString()
                : null;

            $recepcionMoto = RecepcionMoto::create([
                'empresa_id' => $empresaId,
                'almacen_id' => $datos['almacen_id'],
                'proveedor_id' => $datos['proveedor_id'],
                'user_id' => $user->id,
                'codigo' => $this->generarCodigo($empresaId),
                'tipo_documento' => $datos['tipo_documento'] ?? 'factura',
                'numero_documento' => trim($datos['numero_documento']),
                'numero_control' => ! empty($datos['numero_control']) ? trim($datos['numero_control']) : null,
                'moneda_documento' => $monedaDoc,
                'tasa_cambio' => $tasaCambio,
                'tasa_compra' => $tasaCompra,
                'tasa_venta' => $tasaVenta,
                'fecha_emision' => $datos['fecha_emision'],
                'fecha_recepcion' => $datos['fecha_recepcion'],
                'condicion_pago' => $datos['condicion_pago'],
                'dias_credito' => $diasCredito,
                'fecha_vencimiento' => $fechaVencimiento,
                'monto_bruto_usd' => $montoBrutoUsd,
                'monto_bruto_bs' => $montoBrutoBs,
                'base_imponible_usd' => $baseImponibleUsd,
                'base_imponible_bs' => $baseImponibleBs,
                'exento_usd' => $exentoUsd,
                'exento_bs' => $exentoBs,
                'descuento_global_porcentaje' => $descGlobalPct,
                'descuento_global_usd' => $descGlobalUsd,
                'descuento_global_bs' => $descGlobalBs,
                'subtotal_usd' => $subtotalGlobalUsd,
                'subtotal_bs' => $subtotalBs,
                'iva_porcentaje' => $ivaPorcentajeGeneral,
                'iva_usd' => $ivaGlobalUsd,
                'iva_bs' => $ivaBs,
                'flete_total_usd' => $fleteGlobalUsd,
                'flete_total_bs' => $fleteGlobalBs,
                'incluir_flete_en_factura' => $incluirFlete,
                'total_usd' => $totalGlobalUsd,
                'total_bs' => $totalBs,
                'total_unidades' => $totalUnidades,
                'observaciones' => $datos['observaciones'] ?? null,
                'estado' => 'procesada',
            ]);

            // 4. Crear detalles, motos individuales y procesar stock/Kardex de productos
            foreach ($detallesCalculados as $det) {
                $seriales = $det['seriales'];
                unset($det['seriales']);

                $detalleModel = $recepcionMoto->detalles()->create($det);

                if ($det['tipo_item'] === 'moto') {
                    foreach ($seriales as $serial) {
                        $almacenMotoId = ! empty($serial['almacen_id']) ? (int) $serial['almacen_id'] : $det['almacen_id'];

                        Moto::create([
                            'empresa_id' => $empresaId,
                            'almacen_id' => $almacenMotoId,
                            'proveedor_id' => $datos['proveedor_id'],
                            'recepcion_moto_id' => $recepcionMoto->id,
                            'recepcion_moto_detalle_id' => $detalleModel->id,
                            'referencia' => $det['referencia'],
                            'marca' => $det['marca'],
                            'modelo' => $det['modelo'],
                            'anio' => $det['anio'],
                            'color' => $det['color'],
                            'cilindrada' => $det['cilindrada'],
                            'numero_niv' => strtoupper(trim($serial['numero_niv'])),
                            'numero_chasis' => strtoupper(trim($serial['numero_chasis'])),
                            'numero_motor' => strtoupper(trim($serial['numero_motor'])),
                            'certificado_origen' => strtoupper(trim($serial['certificado_origen'])),
                            'placa' => ! empty($serial['placa']) ? strtoupper(trim($serial['placa'])) : null,
                            'costo_base_usd' => $det['costo_unitario_usd'],
                            'costo_base_bs' => $det['costo_unitario_bs'],
                            'flete_usd' => $det['flete_unitario_usd'],
                            'flete_bs' => $det['flete_unitario_bs'],
                            'precio_costo_usd' => $det['costo_total_unitario_usd'],
                            'precio_costo_bs' => $det['costo_total_unitario_bs'],
                            'margen_detal' => $det['margen_detal'],
                            'precio_detal_usd' => $det['precio_detal_usd'],
                            'precio_detal_bs' => $det['precio_detal_bs'],
                            'precio_detal_con_iva_usd' => $det['precio_detal_con_iva_usd'],
                            'precio_detal_con_iva_bs' => $det['precio_detal_con_iva_bs'],
                            'margen_mayorista' => $det['margen_mayorista'],
                            'precio_mayorista_usd' => $det['precio_mayorista_usd'],
                            'precio_mayorista_bs' => $det['precio_mayorista_bs'],
                            'precio_mayorista_con_iva_usd' => $det['precio_mayorista_con_iva_usd'],
                            'precio_mayorista_con_iva_bs' => $det['precio_mayorista_con_iva_bs'],
                            'estado' => 'disponible',
                            'observaciones' => "Ingresada en recepción {$recepcionMoto->codigo}",
                        ]);
                    }
                } elseif ($det['tipo_item'] === 'producto') {
                    $producto = Producto::where('id', $det['producto_id'])
                        ->where('empresa_id', $empresaId)
                        ->firstOrFail();

                    // Actualizar catálogo del producto
                    $producto->precio_costo_usd = $det['costo_total_unitario_usd'];
                    $producto->precio_costo_bs = $det['costo_total_unitario_bs'];
                    $producto->precio_detal_usd = $det['precio_detal_usd'];
                    $producto->precio_detal_bs = $det['precio_detal_bs'];
                    $producto->precio_mayorista_usd = $det['precio_mayorista_usd'];
                    $producto->precio_mayorista_bs = $det['precio_mayorista_bs'];
                    $producto->ultimo_margen_detal = $det['margen_detal'];
                    $producto->ultimo_margen_mayorista = $det['margen_mayorista'];
                    $producto->save();

                    // Vincular ProductoProveedor
                    ProductoProveedor::updateOrCreate(
                        [
                            'producto_id' => $producto->id,
                            'proveedor_id' => $datos['proveedor_id'],
                        ],
                        [
                            'ultimo_costo_usd' => $det['costo_total_unitario_usd'],
                            'ultimo_costo_bs' => $det['costo_total_unitario_bs'],
                        ]
                    );

                    // Registrar Kardex
                    $this->kardexService->registrarMovimiento([
                        'empresa_id' => $empresaId,
                        'almacen_id' => $det['almacen_id'],
                        'producto_id' => $producto->id,
                        'user_id' => $user->id,
                        'tipo_movimiento' => 'entrada_recepcion',
                        'documento_tipo' => 'recepcion_moto',
                        'documento_id' => $recepcionMoto->id,
                        'cantidad' => $det['cantidad'],
                        'costo_unitario_usd' => $det['costo_total_unitario_usd'],
                        'costo_unitario_bs' => $det['costo_total_unitario_bs'],
                        'motivo' => "Recepción {$recepcionMoto->codigo} - Factura {$recepcionMoto->numero_documento}",
                    ]);
                }
            }

            // 5. Si es a crédito, registrar en Cuentas por Pagar (CXP)
            if ($datos['condicion_pago'] === 'credito') {
                CuentaPorPagar::create([
                    'empresa_id' => $empresaId,
                    'proveedor_id' => $datos['proveedor_id'],
                    'recepcion_id' => null,
                    'numero_factura' => $datos['numero_documento'],
                    'fecha_emision' => $datos['fecha_emision'],
                    'fecha_vencimiento' => $fechaVencimiento,
                    'monto_total_usd' => $totalGlobalUsd,
                    'monto_total_bs' => $totalBs,
                    'monto_pagado_usd' => 0,
                    'monto_pagado_bs' => 0,
                    'saldo_pendiente_usd' => $totalGlobalUsd,
                    'saldo_pendiente_bs' => $totalBs,
                    'estado' => 'pendiente',
                    'observaciones' => "Factura de compra {$recepcionMoto->codigo}",
                ]);
            }

            return $recepcionMoto;
        });
    }

    /**
     * Anular una recepción de motos y revertir stock/Kardex
     */
    public function anular(int $id, int $empresaId): RecepcionMoto
    {
        return DB::transaction(function () use ($id, $empresaId) {
            $recepcion = RecepcionMoto::with(['motos', 'detalles.producto'])
                ->where('empresa_id', $empresaId)
                ->findOrFail($id);

            if ($recepcion->estado === 'anulada') {
                throw new \Exception('Esta recepción ya se encuentra anulada.');
            }

            $motosVendidas = $recepcion->motos->where('estado', 'vendida');
            if ($motosVendidas->isNotEmpty()) {
                throw new \Exception("No se puede anular la recepción porque {$motosVendidas->count()} moto(s) ya han sido vendidas.");
            }

            // Anular motos
            foreach ($recepcion->motos as $moto) {
                $moto->estado = 'anulada';
                $moto->save();
            }

            // Revertir inventario de productos vía Kardex
            foreach ($recepcion->detalles as $detalle) {
                if ($detalle->tipo_item === 'producto' && $detalle->producto_id) {
                    $this->kardexService->registrarMovimiento([
                        'empresa_id' => $empresaId,
                        'almacen_id' => $detalle->almacen_id,
                        'producto_id' => $detalle->producto_id,
                        'user_id' => $recepcion->user_id,
                        'tipo_movimiento' => 'anulacion_recepcion',
                        'documento_tipo' => 'recepcion_moto',
                        'documento_id' => $recepcion->id,
                        'cantidad' => (float) $detalle->cantidad,
                        'costo_unitario_usd' => (float) $detalle->costo_unitario_usd,
                        'costo_unitario_bs' => (float) $detalle->costo_unitario_bs,
                        'motivo' => "Anulación de Recepción Motos {$recepcion->codigo} (Doc: {$recepcion->numero_documento})",
                    ]);
                }
            }

            $recepcion->estado = 'anulada';
            $recepcion->save();

            CuentaPorPagar::where('numero_factura', $recepcion->numero_documento)
                ->where('proveedor_id', $recepcion->proveedor_id)
                ->where('empresa_id', $empresaId)
                ->update(['estado' => 'anulada']);

            return $recepcion;
        });
    }
}
