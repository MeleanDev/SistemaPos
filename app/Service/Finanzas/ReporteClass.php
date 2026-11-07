<?php

namespace App\Service\Finanzas;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\CajaTurno;
use App\Models\Categoria;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorCobrarAbono;
use App\Models\CuentaPorPagar;
use App\Models\CuentaPorPagarAbono;
use App\Models\DevolucionVenta;
use App\Models\Empresa;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\ProductoStockAlmacen;
use App\Models\Vendedor;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaPago;
use App\Queries\VentaNetaQuery;
use App\Service\Empresa\CajaClass;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ReporteClass
{
    /**
     * Catálogos para filtros de reportes (cajas, almacenes, categorías, métodos de pago, vendedores)
     */
    public function catalogosFiltros(int $empresaId): array
    {
        $empresa = Empresa::findOrFail($empresaId);

        $cajas = Caja::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

        $almacenes = Almacen::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo']);

        $categorias = Categoria::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $metodosPago = MetodoPago::where('estado', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'descripcion']);

        $vendedores = [];
        if ($empresa->maneja_vendedores) {
            $vendedores = Vendedor::where('empresa_id', $empresaId)
                ->where('estado', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'tipo_documento', 'documento', 'comision_porcentaje']);
        }

        $productos = Producto::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->where('tipo', '!=', 'servicio')
            ->orderBy('nombre')
            ->get(['id', 'codigo_interno', 'nombre']);

        return [
            'cajas' => $cajas,
            'almacenes' => $almacenes,
            'categorias' => $categorias,
            'metodos_pago' => $metodosPago,
            'vendedores' => $vendedores,
            'productos' => $productos,
            'maneja_vendedores' => (bool) $empresa->maneja_vendedores,
            'tasa_usd' => (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1),
            'empresa_nombre' => $empresa->nombre_comercial,
        ];
    }

    /**
     * Reporte 1: Ingresos y Desglose por Método de Pago y Caja
     */
    public function reporteIngresos(int $empresaId, array $filtros): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $tasaCambio = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);

        $fechaInicio = ! empty($filtros['fecha_inicio']) ? Carbon::parse($filtros['fecha_inicio'])->startOfDay() : Carbon::now()->startOfMonth();
        $fechaFin = ! empty($filtros['fecha_fin']) ? Carbon::parse($filtros['fecha_fin'])->endOfDay() : Carbon::now()->endOfDay();

        $cajaId = ! empty($filtros['caja_id']) ? (int) $filtros['caja_id'] : null;
        $metodoPagoId = ! empty($filtros['metodo_pago_id']) ? (int) $filtros['metodo_pago_id'] : null;

        // Consulta base de ventas efectivas con venta neta
        $queryVentas = VentaNetaQuery::conVentaNeta(
            VentaNetaQuery::paraPeriodo($empresaId, $fechaInicio->toDateString(), $fechaFin->toDateString())
        );

        if ($cajaId) {
            $queryVentas->where('caja_id', $cajaId);
        }

        $ventas = $queryVentas->with(['pagos.metodoPago', 'cliente', 'caja', 'usuario', 'devoluciones'])->get();

        $totalesVentas = VentaNetaQuery::calcularTotales(
            VentaNetaQuery::paraPeriodo($empresaId, $fechaInicio->toDateString(), $fechaFin->toDateString())
                ->when($cajaId, fn ($q) => $q->where('caja_id', $cajaId))
        );

        // Facturas anuladas en el período
        $queryAnuladas = Venta::where('empresa_id', $empresaId)
            ->where('estado', 'anulada')
            ->whereBetween('fecha_emision', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->when($cajaId, fn ($q) => $q->where('caja_id', $cajaId));

        $totalAnuladasUsd = round((float) $queryAnuladas->sum('total_usd'), 2);
        $totalAnuladasBs = round((float) $queryAnuladas->sum('total_bs'), 2);
        $conteoAnuladas = $queryAnuladas->count();

        $totalBrutoUsd = $totalesVentas['total_bruto_usd'];
        $totalBrutoBs = $totalesVentas['total_bruto_bs'];
        $totalDevueltoUsd = $totalesVentas['total_devuelto_usd'];
        $totalDevueltoBs = $totalesVentas['total_devuelto_bs'];
        $totalNetoUsd = $totalesVentas['total_neto_usd'];
        $totalNetoBs = $totalesVentas['total_neto_bs'];

        $totalFacturadoUsd = $totalNetoUsd;
        $totalFacturadoBs = $totalNetoBs;
        $totalPagadoUsd = round((float) $ventas->sum('monto_pagado_usd'), 2);
        $totalPagadoBs = round((float) $ventas->sum('monto_pagado_bs'), 2);
        $totalVueltoUsd = round((float) $ventas->sum('vuelto_usd'), 2);
        $totalVueltoBs = round((float) $ventas->sum('vuelto_bs'), 2);
        $totalPorCobrarUsd = round((float) $ventas->sum('saldo_pendiente_usd'), 2);
        $totalPorCobrarBs = round((float) $ventas->sum('saldo_pendiente_bs'), 2);
        $conteoVentas = $totalesVentas['conteo_ventas'];
        $ticketPromedioUsd = $totalesVentas['ticket_promedio_usd'];

        // Desglose por Método de Pago en el período
        $metodosCatalogo = MetodoPago::where('estado', true)->orderBy('nombre')->get();
        $desgloseMetodos = [];

        $queryPagos = VentaPago::whereHas('venta', function ($q) use ($empresaId, $fechaInicio, $fechaFin, $cajaId) {
            $q->where('empresa_id', $empresaId)
                ->where('estado', '!=', 'anulada')
                ->whereBetween('fecha_emision', [$fechaInicio->toDateString(), $fechaFin->toDateString()]);

            if ($cajaId) {
                $q->where('caja_id', $cajaId);
            }
        });

        if ($metodoPagoId) {
            $queryPagos->where('metodo_pago_id', $metodoPagoId);
        }

        $pagos = $queryPagos->with('metodoPago')->get();

        foreach ($metodosCatalogo as $metodo) {
            if ($metodoPagoId && $metodo->id !== $metodoPagoId) {
                continue;
            }

            $pagosMetodo = $pagos->where('metodo_pago_id', $metodo->id);
            $totalMontoUsd = round((float) $pagosMetodo->sum('monto_usd'), 2);
            $totalMontoBs = round((float) $pagosMetodo->sum('monto_bs'), 2);
            $transaccionesCount = $pagosMetodo->count();

            $porcentaje = $totalPagadoUsd > 0 ? round(($totalMontoUsd / $totalPagadoUsd) * 100, 1) : 0;

            $desgloseMetodos[] = [
                'metodo_id' => $metodo->id,
                'nombre' => $metodo->nombre,
                'total_usd' => $totalMontoUsd,
                'total_bs' => $totalMontoBs,
                'transacciones_count' => $transaccionesCount,
                'porcentaje' => $porcentaje,
            ];
        }

        // Si hubo ventas a crédito
        if (! $metodoPagoId && $totalPorCobrarUsd > 0) {
            $ventasCredito = $ventas->where('saldo_pendiente_usd', '>', 0);
            $desgloseMetodos[] = [
                'metodo_id' => 0,
                'nombre' => 'Crédito / Cuentas por Cobrar',
                'total_usd' => $totalPorCobrarUsd,
                'total_bs' => $totalPorCobrarBs,
                'transacciones_count' => $ventasCredito->count(),
                'porcentaje' => $totalFacturadoUsd > 0 ? round(($totalPorCobrarUsd / $totalFacturadoUsd) * 100, 1) : 0,
            ];
        }

        // Listado de transacciones
        $transacciones = $ventas->map(function ($v) {
            $metodosNombres = $v->pagos->map(fn ($p) => $p->metodoPago?->nombre ?? 'N/A')->unique()->implode(', ');
            if ($v->saldo_pendiente_usd > 0) {
                $metodosNombres .= ($metodosNombres ? ', ' : '').'Crédito';
            }

            return [
                'id' => $v->id,
                'codigo' => $v->codigo,
                'numero_control' => $v->numero_control ?: 'N/A',
                'fecha' => Carbon::parse($v->fecha_emision)->format('d/m/Y'),
                'hora' => $v->hora_emision ? Carbon::parse($v->hora_emision)->format('h:i A') : '',
                'cliente' => $v->cliente?->nombre ?: 'Consumidor Final',
                'caja' => $v->caja?->nombre ?: 'Caja Principal',
                'cajero' => $v->usuario?->name ?: 'Sistema',
                'metodos' => $metodosNombres ?: 'Efectivo',
                'total_bruto_usd' => (float) $v->total_usd,
                'total_devuelto_usd' => (float) ($v->total_devuelto_usd ?? 0),
                'total_usd' => (float) ($v->venta_neta_usd ?? $v->total_usd),
                'total_bs' => (float) ($v->venta_neta_bs ?? $v->total_bs),
                'monto_pagado_usd' => (float) $v->monto_pagado_usd,
                'saldo_pendiente_usd' => (float) $v->saldo_pendiente_usd,
                'estado' => $v->estado,
            ];
        });

        return [
            'kpis' => [
                'total_bruto_usd' => $totalBrutoUsd,
                'total_bruto_bs' => $totalBrutoBs,
                'total_devoluciones_usd' => $totalDevueltoUsd,
                'total_devoluciones_bs' => $totalDevueltoBs,
                'total_anuladas_usd' => $totalAnuladasUsd,
                'total_anuladas_bs' => $totalAnuladasBs,
                'conteo_anuladas' => $conteoAnuladas,
                'total_neto_usd' => $totalNetoUsd,
                'total_neto_bs' => $totalNetoBs,
                'total_facturado_usd' => $totalNetoUsd,
                'total_facturado_bs' => $totalNetoBs,
                'total_pagado_usd' => $totalPagadoUsd,
                'total_pagado_bs' => $totalPagadoBs,
                'total_vuelto_usd' => $totalVueltoUsd,
                'total_por_cobrar_usd' => $totalPorCobrarUsd,
                'conteo_ventas' => $conteoVentas,
                'ticket_promedio_usd' => $ticketPromedioUsd,
                'tasa_cambio' => $tasaCambio,
                'periodo_texto' => "Del {$fechaInicio->format('d/m/Y')} al {$fechaFin->format('d/m/Y')}",
            ],
            'desglose_metodos' => $desgloseMetodos,
            'transacciones' => $transacciones,
        ];
    }

    /**
     * Reporte 2: Cuentas por Cobrar (CXC) y Cuentas por Pagar (CXP)
     */
    public function reporteCreditos(int $empresaId, array $filtros): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $tasaCambio = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);
        $hoy = Carbon::now()->startOfDay();

        $fechaInicio = ! empty($filtros['fecha_inicio']) ? Carbon::parse($filtros['fecha_inicio'])->startOfDay() : Carbon::now()->startOfMonth();
        $fechaFin = ! empty($filtros['fecha_fin']) ? Carbon::parse($filtros['fecha_fin'])->endOfDay() : Carbon::now()->endOfDay();

        // 1. CUENTAS POR COBRAR (CLIENTES)
        $cuentasPorCobrarPendientes = CuentaPorCobrar::with(['cliente', 'venta'])
            ->where('empresa_id', $empresaId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->orderBy('fecha_vencimiento')
            ->get();

        $totalCxcUsd = round((float) $cuentasPorCobrarPendientes->sum('saldo_pendiente_usd'), 2);
        $totalCxcBs = round($totalCxcUsd * $tasaCambio, 2);

        $cxcAlDiaUsd = 0;
        $cxcPorVencerUsd = 0;
        $cxcMora15Usd = 0;
        $cxcMora30Usd = 0;
        $cxcMora60Usd = 0;

        foreach ($cuentasPorCobrarPendientes as $c) {
            $vencimiento = Carbon::parse($c->fecha_vencimiento)->startOfDay();
            $dias = (int) $vencimiento->diffInDays($hoy, false); // positivo = días de mora, negativo = días para vencer
            $saldo = (float) $c->saldo_pendiente_usd;

            if ($vencimiento->gte($hoy)) {
                $diasParaVencer = abs($dias);
                if ($diasParaVencer <= 7) {
                    $cxcPorVencerUsd += $saldo;
                } else {
                    $cxcAlDiaUsd += $saldo;
                }
            } else {
                $diasMora = abs($dias);
                if ($diasMora <= 15) {
                    $cxcMora15Usd += $saldo;
                } elseif ($diasMora <= 30) {
                    $cxcMora30Usd += $saldo;
                } else {
                    $cxcMora60Usd += $saldo;
                }
            }
        }

        // Abonos recaudados en el período
        $abonosCxcPeriodo = CuentaPorCobrarAbono::whereHas('cuentaPorCobrar', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        })->whereBetween('fecha_abono', [$fechaInicio->toDateString(), $fechaFin->toDateString()])->get();

        $totalAbonosCxcUsd = round((float) $abonosCxcPeriodo->sum('monto_usd'), 2);
        $totalAbonosCxcBs = round((float) $abonosCxcPeriodo->sum('monto_bs'), 2);

        // 2. CUENTAS POR PAGAR (PROVEEDORES)
        $cuentasPorPagarPendientes = CuentaPorPagar::with(['proveedor'])
            ->where('empresa_id', $empresaId)
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->orderBy('fecha_vencimiento')
            ->get();

        $totalCxpUsd = round((float) $cuentasPorPagarPendientes->sum('saldo_pendiente_usd'), 2);
        $totalCxpBs = round($totalCxpUsd * $tasaCambio, 2);

        $cxpAlDiaUsd = 0;
        $cxpPorVencerUsd = 0;
        $cxpVencidasUsd = 0;

        foreach ($cuentasPorPagarPendientes as $p) {
            $vencimiento = Carbon::parse($p->fecha_vencimiento)->startOfDay();
            $saldo = (float) $p->saldo_pendiente_usd;

            if ($vencimiento->gte($hoy)) {
                $diasRestantes = (int) $hoy->diffInDays($vencimiento, false);
                if ($diasRestantes <= 7) {
                    $cxpPorVencerUsd += $saldo;
                } else {
                    $cxpAlDiaUsd += $saldo;
                }
            } else {
                $cxpVencidasUsd += $saldo;
            }
        }

        // Abonos pagados a proveedores en el período
        $abonosCxpPeriodo = CuentaPorPagarAbono::whereHas('cuentaPorPagar', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        })->whereBetween('fecha_abono', [$fechaInicio->toDateString(), $fechaFin->toDateString()])->get();

        $totalAbonosCxpUsd = round((float) $abonosCxpPeriodo->sum('monto_usd'), 2);
        $totalAbonosCxpBs = round((float) $abonosCxpPeriodo->sum('monto_bs'), 2);

        return [
            'kpis' => [
                'total_cxc_usd' => $totalCxcUsd,
                'total_cxc_bs' => $totalCxcBs,
                'cxc_al_dia_usd' => round($cxcAlDiaUsd, 2),
                'cxc_por_vencer_usd' => round($cxcPorVencerUsd, 2),
                'cxc_en_mora_usd' => round($cxcMora15Usd + $cxcMora30Usd + $cxcMora60Usd, 2),
                'total_abonos_cxc_usd' => $totalAbonosCxcUsd,
                'total_abonos_cxc_bs' => $totalAbonosCxcBs,
                'total_cxp_usd' => $totalCxpUsd,
                'total_cxp_bs' => $totalCxpBs,
                'cxp_al_dia_usd' => round($cxpAlDiaUsd, 2),
                'cxp_por_vencer_usd' => round($cxpPorVencerUsd, 2),
                'cxp_vencidas_usd' => round($cxpVencidasUsd, 2),
                'total_abonos_cxp_usd' => $totalAbonosCxpUsd,
                'total_abonos_cxp_bs' => $totalAbonosCxpBs,
                'tasa_cambio' => $tasaCambio,
            ],
            'antiguedad_cxc' => [
                'al_dia' => round($cxcAlDiaUsd, 2),
                'por_vencer_7d' => round($cxcPorVencerUsd, 2),
                'mora_1_15d' => round($cxcMora15Usd, 2),
                'mora_16_30d' => round($cxcMora30Usd, 2),
                'mora_mas_30d' => round($cxcMora60Usd, 2),
            ],
            'detalle_cxc' => $cuentasPorCobrarPendientes->map(function ($c) use ($hoy) {
                $venc = Carbon::parse($c->fecha_vencimiento)->startOfDay();

                return [
                    'id' => $c->id,
                    'cliente_nombre' => $c->cliente?->nombre ?: 'Cliente Desconocido',
                    'cliente_cedula' => $c->cliente?->cedula ?: 'S/D',
                    'cliente_telefono' => $c->cliente?->telefono ?: 'Sin teléfono',
                    'numero_factura' => $c->numero_factura,
                    'fecha_emision' => Carbon::parse($c->fecha_emision)->format('d/m/Y'),
                    'fecha_vencimiento' => $venc->format('d/m/Y'),
                    'monto_total_usd' => (float) $c->monto_total_usd,
                    'monto_pagado_usd' => (float) $c->monto_pagado_usd,
                    'saldo_pendiente_usd' => (float) $c->saldo_pendiente_usd,
                    'saldo_pendiente_bs' => (float) $c->saldo_pendiente_bs,
                    'es_vencida' => $venc->lt($hoy),
                    'dias' => (int) $hoy->diffInDays($venc, false),
                ];
            }),
            'detalle_cxp' => $cuentasPorPagarPendientes->map(function ($p) use ($hoy) {
                $venc = Carbon::parse($p->fecha_vencimiento)->startOfDay();

                return [
                    'id' => $p->id,
                    'proveedor_nombre' => $p->proveedor?->nombre ?: 'Proveedor Desconocido',
                    'proveedor_rif' => $p->proveedor?->rif ?: 'S/D',
                    'proveedor_telefono' => $p->proveedor?->telefono ?: 'Sin teléfono',
                    'numero_factura' => $p->numero_factura,
                    'fecha_emision' => Carbon::parse($p->fecha_emision)->format('d/m/Y'),
                    'fecha_vencimiento' => $venc->format('d/m/Y'),
                    'monto_total_usd' => (float) $p->monto_total_usd,
                    'monto_pagado_usd' => (float) $p->monto_pagado_usd,
                    'saldo_pendiente_usd' => (float) $p->saldo_pendiente_usd,
                    'saldo_pendiente_bs' => (float) $p->saldo_pendiente_bs,
                    'es_vencida' => $venc->lt($hoy),
                    'dias' => (int) $hoy->diffInDays($venc, false),
                ];
            }),
        ];
    }

    /**
     * Reporte 3: Inventario Completo y Valorización por Almacén / Producto
     */
    public function reporteInventario(int $empresaId, array $filtros): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $tasaCambio = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);

        $almacenId = ! empty($filtros['almacen_id']) ? (int) $filtros['almacen_id'] : null;
        $categoriaId = ! empty($filtros['categoria_id']) ? (int) $filtros['categoria_id'] : null;
        $soloBajoStock = ! empty($filtros['bajo_stock']);

        $query = ProductoStockAlmacen::with(['producto.categoria', 'almacen'])
            ->where('cantidad_actual', '>', 0)
            ->whereHas('producto', function (Builder $q) use ($empresaId, $categoriaId) {
                $q->where('empresa_id', $empresaId)->where('estado', true);
                if ($categoriaId) {
                    $q->where('categoria_id', $categoriaId);
                }
            })
            ->whereHas('almacen', function (Builder $q) use ($empresaId, $almacenId) {
                $q->where('empresa_id', $empresaId)->where('estado', true);
                if ($almacenId) {
                    $q->where('id', $almacenId);
                }
            });

        $stocks = $query->get();

        $totalItems = $stocks->pluck('producto_id')->unique()->count();
        $totalUnidadesFisicas = round((float) $stocks->sum('cantidad_actual'), 2);
        $conteoStockBajo = 0;

        $valorTotalCostoUsd = 0;
        $valorTotalVentaUsd = 0;

        $filasInventario = [];

        foreach ($stocks as $item) {
            /** @var Producto $prod */
            $prod = $item->producto;
            $stockActual = (float) $item->cantidad_actual;
            $stockMinimo = (float) ($prod->stock_minimo ?? 0);
            $costoUnitarioUsd = (float) ($prod->precio_costo_usd ?? 0);
            $precioVentaUsd = (float) ($prod->precio_detal_usd ?? 0);

            $costoSubtotal = round($stockActual * $costoUnitarioUsd, 2);
            $ventaSubtotal = round($stockActual * $precioVentaUsd, 2);

            $esBajoStock = $stockActual <= $stockMinimo;
            if ($esBajoStock) {
                $conteoStockBajo++;
            }

            if ($soloBajoStock && ! $esBajoStock) {
                continue;
            }

            $valorTotalCostoUsd += $costoSubtotal;
            $valorTotalVentaUsd += $ventaSubtotal;

            $filasInventario[] = [
                'id' => $item->id,
                'producto_id' => $prod->id,
                'codigo_interno' => $prod->codigo_interno ?: 'S/C',
                'nombre' => $prod->nombre,
                'categoria' => $prod->categoria?->nombre ?: 'Sin Categoría',
                'almacen' => $item->almacen?->nombre ?: 'Almacén Central',
                'ubicacion' => $item->ubicacion_pasillo ?: ($item->almacen?->nombre ?: 'Piso'),
                'stock_actual' => $stockActual,
                'stock_minimo' => $stockMinimo,
                'es_bajo_stock' => $esBajoStock,
                'costo_unitario_usd' => $costoUnitarioUsd,
                'costo_unitario_bs' => round($costoUnitarioUsd * $tasaCambio, 2),
                'precio_venta_usd' => $precioVentaUsd,
                'precio_venta_bs' => round($precioVentaUsd * $tasaCambio, 2),
                'costo_subtotal_usd' => $costoSubtotal,
                'costo_subtotal_bs' => round($costoSubtotal * $tasaCambio, 2),
                'venta_subtotal_usd' => $ventaSubtotal,
                'venta_subtotal_bs' => round($ventaSubtotal * $tasaCambio, 2),
            ];
        }

        $margenGananciaUsd = round($valorTotalVentaUsd - $valorTotalCostoUsd, 2);
        $margenGananciaBs = round($margenGananciaUsd * $tasaCambio, 2);
        $margenPorcentaje = $valorTotalCostoUsd > 0 ? round(($margenGananciaUsd / $valorTotalCostoUsd) * 100, 1) : 0;

        return [
            'kpis' => [
                'total_items' => $totalItems,
                'total_unidades' => $totalUnidadesFisicas,
                'valor_costo_usd' => round($valorTotalCostoUsd, 2),
                'valor_costo_bs' => round($valorTotalCostoUsd * $tasaCambio, 2),
                'valor_venta_usd' => round($valorTotalVentaUsd, 2),
                'valor_venta_bs' => round($valorTotalVentaUsd * $tasaCambio, 2),
                'margen_proyectado_usd' => $margenGananciaUsd,
                'margen_proyectado_bs' => $margenGananciaBs,
                'margen_porcentaje' => $margenPorcentaje,
                'conteo_bajo_stock' => $conteoStockBajo,
                'tasa_cambio' => $tasaCambio,
            ],
            'inventario' => $filasInventario,
        ];
    }

    /**
     * Reporte 4: Rentabilidad Real, Ganancia Bruta y Top Productos Más Vendidos
     */
    public function reporteRentabilidad(int $empresaId, array $filtros): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $tasaCambio = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);

        $fechaInicio = ! empty($filtros['fecha_inicio']) ? Carbon::parse($filtros['fecha_inicio'])->startOfDay() : Carbon::now()->startOfMonth();
        $fechaFin = ! empty($filtros['fecha_fin']) ? Carbon::parse($filtros['fecha_fin'])->endOfDay() : Carbon::now()->endOfDay();

        $almacenId = ! empty($filtros['almacen_id']) ? (int) $filtros['almacen_id'] : null;

        $queryVentas = VentaNetaQuery::validas($empresaId)
            ->whereBetween('fecha_emision', [$fechaInicio->toDateString(), $fechaFin->toDateString()]);

        if ($almacenId) {
            $queryVentas->where('almacen_id', $almacenId);
        }

        $ventasIds = $queryVentas->pluck('id');

        $detalles = VentaDetalle::with(['producto.categoria', 'servicio', 'devoluciones'])
            ->whereIn('venta_id', $ventasIds)
            ->get();

        $totalIngresosUsd = 0;
        $totalCostoUsd = 0;

        $productosAgrupados = [];

        foreach ($detalles as $d) {
            $cantDevuelta = (float) $d->devoluciones->sum('cantidad');
            $cantidad = max(0.0, (float) $d->cantidad - $cantDevuelta);
            $subtotalDevueltoUsd = (float) $d->devoluciones->sum('subtotal_usd');
            $ingresoItem = max(0.0, (float) $d->subtotal_usd - $subtotalDevueltoUsd);
            $costoUnitario = (float) $d->costo_unitario_usd;
            $costoTotalItem = round($cantidad * $costoUnitario, 2);
            $gananciaItem = round($ingresoItem - $costoTotalItem, 2);

            $totalIngresosUsd += $ingresoItem;
            $totalCostoUsd += $costoTotalItem;

            $key = ($d->tipo_item ?: 'producto').'_'.($d->producto_id ?: $d->servicio_id ?: $d->nombre_item);

            if (! isset($productosAgrupados[$key])) {
                $productosAgrupados[$key] = [
                    'nombre' => $d->nombre_item,
                    'tipo' => strtoupper($d->tipo_item ?: 'PRODUCTO'),
                    'categoria' => $d->producto?->categoria?->nombre ?: 'General',
                    'cantidad_vendida' => 0,
                    'ingreso_total_usd' => 0,
                    'costo_total_usd' => 0,
                    'ganancia_bruta_usd' => 0,
                ];
            }

            $productosAgrupados[$key]['cantidad_vendida'] += $cantidad;
            $productosAgrupados[$key]['ingreso_total_usd'] += $ingresoItem;
            $productosAgrupados[$key]['costo_total_usd'] += $costoTotalItem;
            $productosAgrupados[$key]['ganancia_bruta_usd'] += $gananciaItem;
        }

        $gananciaBrutaTotalUsd = round($totalIngresosUsd - $totalCostoUsd, 2);
        $gananciaBrutaTotalBs = round($gananciaBrutaTotalUsd * $tasaCambio, 2);
        $margenBrutoPorcentaje = $totalIngresosUsd > 0 ? round(($gananciaBrutaTotalUsd / $totalIngresosUsd) * 100, 1) : 0;

        // Ordenar Top 10 productos más vendidos
        $topProductos = collect($productosAgrupados)
            ->sortByDesc('ingreso_total_usd')
            ->values()
            ->take(20)
            ->map(function ($p) use ($tasaCambio) {
                $margenItem = $p['ingreso_total_usd'] > 0 ? round(($p['ganancia_bruta_usd'] / $p['ingreso_total_usd']) * 100, 1) : 0;

                return [
                    'nombre' => $p['nombre'],
                    'tipo' => $p['tipo'],
                    'categoria' => $p['categoria'],
                    'cantidad_vendida' => round($p['cantidad_vendida'], 2),
                    'ingreso_total_usd' => round($p['ingreso_total_usd'], 2),
                    'ingreso_total_bs' => round($p['ingreso_total_usd'] * $tasaCambio, 2),
                    'costo_total_usd' => round($p['costo_total_usd'], 2),
                    'ganancia_bruta_usd' => round($p['ganancia_bruta_usd'], 2),
                    'ganancia_bruta_bs' => round($p['ganancia_bruta_usd'] * $tasaCambio, 2),
                    'margen_porcentaje' => $margenItem,
                ];
            });

        return [
            'kpis' => [
                'total_ingresos_usd' => round($totalIngresosUsd, 2),
                'total_ingresos_bs' => round($totalIngresosUsd * $tasaCambio, 2),
                'total_costo_usd' => round($totalCostoUsd, 2),
                'total_costo_bs' => round($totalCostoUsd * $tasaCambio, 2),
                'ganancia_bruta_usd' => $gananciaBrutaTotalUsd,
                'ganancia_bruta_bs' => $gananciaBrutaTotalBs,
                'margen_bruto_porcentaje' => $margenBrutoPorcentaje,
                'tasa_cambio' => $tasaCambio,
                'periodo_texto' => "Del {$fechaInicio->format('d/m/Y')} al {$fechaFin->format('d/m/Y')}",
            ],
            'top_productos' => $topProductos,
        ];
    }

    /**
     * Reporte 6: Existencias y Stock por Almacén (Matriz Multialmacén)
     */
    public function reporteStockAlmacenes(int $empresaId, array $filtros): array
    {
        $almacenes = Almacen::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('id')
            ->get(['id', 'codigo', 'nombre']);

        $query = Producto::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->where('tipo', '!=', 'servicio');

        if (! empty($filtros['categoria_ids'])) {
            $categoriaIds = is_array($filtros['categoria_ids'])
                ? $filtros['categoria_ids']
                : explode(',', (string) $filtros['categoria_ids']);
            $categoriaIds = array_filter(array_map('intval', $categoriaIds));
            if (! empty($categoriaIds)) {
                $query->whereIn('categoria_id', $categoriaIds);
            }
        } elseif (! empty($filtros['categoria_id'])) {
            $query->where('categoria_id', (int) $filtros['categoria_id']);
        }

        if (! empty($filtros['producto_ids'])) {
            $productoIds = is_array($filtros['producto_ids'])
                ? $filtros['producto_ids']
                : explode(',', (string) $filtros['producto_ids']);
            $productoIds = array_filter(array_map('intval', $productoIds));
            if (! empty($productoIds)) {
                $query->whereIn('id', $productoIds);
            }
        }

        $productos = $query->with([
            'categoria:id,nombre',
            'stockAlmacenes',
        ])->orderBy('nombre')->get();

        $items = [];
        $totalUnidadesGlobal = 0.0;
        $totalesPorAlmacen = [];
        foreach ($almacenes as $alm) {
            $totalesPorAlmacen[$alm->id] = 0.0;
        }
        $productosSinStock = 0;

        foreach ($productos as $prod) {
            $stockMap = [];
            $stockTotalProd = 0.0;

            $stocksRel = $prod->stockAlmacenes->keyBy('almacen_id');

            foreach ($almacenes as $alm) {
                $cant = isset($stocksRel[$alm->id]) ? (float) $stocksRel[$alm->id]->cantidad_actual : 0.0;
                $stockMap[$alm->id] = $cant;
                $stockTotalProd += $cant;
                $totalesPorAlmacen[$alm->id] += $cant;
            }

            if (! empty($filtros['solo_con_stock']) && $stockTotalProd <= 0) {
                continue;
            }

            if ($stockTotalProd <= 0) {
                $productosSinStock++;
            }

            $totalUnidadesGlobal += $stockTotalProd;

            $items[] = [
                'id' => $prod->id,
                'codigo_interno' => $prod->codigo_interno ?: 'S/C',
                'codigo_barra' => $prod->codigo_barra ?: 'S/C',
                'nombre' => $prod->nombre,
                'categoria' => $prod->categoria?->nombre ?: 'General',
                'unidad_medida' => $prod->unidad_medida ?: 'UND',
                'stocks_por_almacen' => $stockMap,
                'stock_total' => round($stockTotalProd, 3),
            ];
        }

        return [
            'kpis' => [
                'total_productos' => count($items),
                'total_unidades' => round($totalUnidadesGlobal, 2),
                'total_almacenes' => $almacenes->count(),
                'productos_sin_stock' => $productosSinStock,
            ],
            'almacenes' => $almacenes->map(fn ($a) => [
                'id' => $a->id,
                'codigo' => $a->codigo,
                'nombre' => $a->nombre,
            ])->values()->toArray(),
            'totales_por_almacen' => array_map(fn ($val) => round($val, 2), $totalesPorAlmacen),
            'items' => $items,
        ];
    }

    /**
     * Reporte 7: Cuadre Diario de Cajas y Turnos
     */
    public function reporteCuadreDiario(int $empresaId, array $filtros): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $tasaCambio = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);

        $fechaInicio = ! empty($filtros['fecha_inicio']) ? Carbon::parse($filtros['fecha_inicio'])->startOfDay() : Carbon::now()->startOfMonth();
        $fechaFin = ! empty($filtros['fecha_fin']) ? Carbon::parse($filtros['fecha_fin'])->endOfDay() : Carbon::now()->endOfDay();
        $cajaId = ! empty($filtros['caja_id']) ? (int) $filtros['caja_id'] : null;

        $cajaClass = app(CajaClass::class);

        $queryTurnos = CajaTurno::with(['caja', 'usuario', 'aperturadoPor'])
            ->where('empresa_id', $empresaId)
            ->whereBetween('fecha_apertura', [$fechaInicio->toDateString(), $fechaFin->toDateString()]);

        if ($cajaId) {
            $queryTurnos->where('caja_id', $cajaId);
        }

        $turnos = $queryTurnos->orderBy('id', 'desc')->get();

        $filas = [];
        $totalAperturaUsd = 0.0;
        $totalVentasBrutoUsd = 0.0;
        $totalDevolucionesUsd = 0.0;
        $totalVentasNetoUsd = 0.0;
        $totalAbonosUsd = 0.0;
        $totalVueltosUsd = 0.0;
        $totalIngresosUsd = 0.0;
        $totalEgresosUsd = 0.0;
        $totalEsperadoUsd = 0.0;
        $totalContadoUsd = 0.0;
        $totalDiferenciaUsd = 0.0;

        foreach ($turnos as $turno) {
            $rep = $cajaClass->calcularReporteTurno($turno->id, $empresaId);

            $montoAperturaUsd = (float) $turno->monto_apertura_usd;
            $ventasBrutoUsd = (float) $rep['total_ventas_usd'];
            $devolucionesUsd = (float) $rep['total_devoluciones_usd'];
            $ventasNetoUsd = (float) ($rep['total_venta_neta_usd'] ?? ($ventasBrutoUsd - $devolucionesUsd));
            $abonosUsd = (float) $rep['total_abonos_cxc_usd'];
            $vueltosUsd = (float) $rep['total_vuelto_usd'];
            $ingresosUsd = (float) $rep['total_ingresos_caja_usd'];
            $egresosUsd = (float) $rep['total_egresos_caja_usd'];
            $esperadoUsd = (float) $rep['efectivo_esperado_usd'];
            $esperadoBs = (float) $rep['efectivo_esperado_bs'];
            $contadoUsd = (float) ($turno->monto_cierre_usd ?? 0);
            $contadoBs = (float) ($turno->monto_cierre_bs ?? 0);

            $diferenciaUsd = $turno->estado === 'cerrada' ? round($contadoUsd - $esperadoUsd, 2) : 0.0;
            $diferenciaBs = $turno->estado === 'cerrada' ? round($contadoBs - $esperadoBs, 2) : 0.0;

            $totalAperturaUsd += $montoAperturaUsd;
            $totalVentasBrutoUsd += $ventasBrutoUsd;
            $totalDevolucionesUsd += $devolucionesUsd;
            $totalVentasNetoUsd += $ventasNetoUsd;
            $totalAbonosUsd += $abonosUsd;
            $totalVueltosUsd += $vueltosUsd;
            $totalIngresosUsd += $ingresosUsd;
            $totalEgresosUsd += $egresosUsd;
            $totalEsperadoUsd += $esperadoUsd;
            $totalContadoUsd += $contadoUsd;
            $totalDiferenciaUsd += $diferenciaUsd;

            $filas[] = [
                'turno_id' => $turno->id,
                'caja_nombre' => $turno->caja?->nombre ?: 'Caja Principal',
                'cajero_nombre' => $turno->usuario?->name ?: 'Sistema',
                'fecha_apertura' => Carbon::parse($turno->fecha_apertura)->format('d/m/Y'),
                'hora_apertura' => $turno->hora_apertura ? Carbon::parse($turno->hora_apertura)->format('h:i A') : '',
                'fecha_cierre' => $turno->fecha_cierre ? Carbon::parse($turno->fecha_cierre)->format('d/m/Y') : 'Abierto',
                'hora_cierre' => $turno->hora_cierre ? Carbon::parse($turno->hora_cierre)->format('h:i A') : '',
                'estado' => $turno->estado,
                'monto_apertura_usd' => $montoAperturaUsd,
                'ventas_bruto_usd' => $ventasBrutoUsd,
                'devoluciones_usd' => $devolucionesUsd,
                'ventas_neto_usd' => $ventasNetoUsd,
                'abonos_cxc_usd' => $abonosUsd,
                'vueltos_usd' => $vueltosUsd,
                'ingresos_caja_usd' => $ingresosUsd,
                'egresos_caja_usd' => $egresosUsd,
                'esperado_usd' => $esperadoUsd,
                'esperado_bs' => $esperadoBs,
                'contado_usd' => $contadoUsd,
                'contado_bs' => $contadoBs,
                'diferencia_usd' => $diferenciaUsd,
                'diferencia_bs' => $diferenciaBs,
            ];
        }

        return [
            'kpis' => [
                'total_turnos' => count($filas),
                'total_apertura_usd' => round($totalAperturaUsd, 2),
                'total_ventas_bruto_usd' => round($totalVentasBrutoUsd, 2),
                'total_devoluciones_usd' => round($totalDevolucionesUsd, 2),
                'total_ventas_neto_usd' => round($totalVentasNetoUsd, 2),
                'total_abonos_usd' => round($totalAbonosUsd, 2),
                'total_vueltos_usd' => round($totalVueltosUsd, 2),
                'total_esperado_usd' => round($totalEsperadoUsd, 2),
                'total_contado_usd' => round($totalContadoUsd, 2),
                'total_diferencia_usd' => round($totalDiferenciaUsd, 2),
                'tasa_cambio' => $tasaCambio,
                'periodo_texto' => "Del {$fechaInicio->format('d/m/Y')} al {$fechaFin->format('d/m/Y')}",
            ],
            'turnos' => $filas,
        ];
    }

    /**
     * Reporte 8: Devoluciones y Anulaciones de Ventas
     */
    public function reporteDevolucionesAnulaciones(int $empresaId, array $filtros): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $tasaCambio = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);

        $fechaInicio = ! empty($filtros['fecha_inicio']) ? Carbon::parse($filtros['fecha_inicio'])->startOfDay() : Carbon::now()->startOfMonth();
        $fechaFin = ! empty($filtros['fecha_fin']) ? Carbon::parse($filtros['fecha_fin'])->endOfDay() : Carbon::now()->endOfDay();
        $tipoFiltro = ! empty($filtros['tipo']) ? trim((string) $filtros['tipo']) : 'todos';

        $filas = [];
        $totalDevolucionesUsd = 0.0;
        $totalDevolucionesBs = 0.0;
        $totalAnuladasUsd = 0.0;
        $totalAnuladasBs = 0.0;

        // 1. Devoluciones
        if ($tipoFiltro === 'todos' || $tipoFiltro === 'devolucion') {
            $devoluciones = DevolucionVenta::with(['venta.cliente', 'usuario', 'metodoPago', 'cajaTurno.caja'])
                ->where('empresa_id', $empresaId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$fechaInicio->toDateString(), $fechaFin->toDateString()])
                ->latest('id')
                ->get();

            foreach ($devoluciones as $dev) {
                $montoUsd = (float) $dev->total_devuelto_usd;
                $montoBs = (float) $dev->total_devuelto_bs;
                $totalDevolucionesUsd += $montoUsd;
                $totalDevolucionesBs += $montoBs;

                $filas[] = [
                    'id' => $dev->id,
                    'tipo' => 'Devolución',
                    'tipo_badge' => 'warning',
                    'codigo' => $dev->codigo,
                    'factura_codigo' => $dev->venta?->codigo ?: 'N/A',
                    'fecha_raw' => $dev->created_at ? $dev->created_at->toDateTimeString() : '',
                    'fecha' => $dev->created_at ? $dev->created_at->format('d/m/Y') : '',
                    'hora' => $dev->created_at ? $dev->created_at->format('h:i A') : '',
                    'usuario' => $dev->usuario?->name ?: 'Sistema',
                    'cliente' => $dev->venta?->cliente?->nombre ?: 'Consumidor Final',
                    'motivo' => $dev->motivo ?: 'Sin motivo especificado',
                    'monto_usd' => $montoUsd,
                    'monto_bs' => $montoBs,
                    'metodo_reembolso' => $dev->tipo_reembolso ? ucfirst(str_replace('_', ' ', $dev->tipo_reembolso)) : 'Efectivo',
                ];
            }
        }

        // 2. Anulaciones
        if ($tipoFiltro === 'todos' || $tipoFiltro === 'anulacion') {
            $anulaciones = Venta::with(['cliente', 'usuario', 'anuladaPor', 'caja'])
                ->where('empresa_id', $empresaId)
                ->where('estado', 'anulada')
                ->whereBetween(DB::raw('DATE(COALESCE(anulada_at, fecha_emision))'), [$fechaInicio->toDateString(), $fechaFin->toDateString()])
                ->latest('id')
                ->get();

            foreach ($anulaciones as $anul) {
                $montoUsd = (float) $anul->total_usd;
                $montoBs = (float) $anul->total_bs;
                $totalAnuladasUsd += $montoUsd;
                $totalAnuladasBs += $montoBs;

                $fechaEvento = $anul->anulada_at ?: $anul->created_at;

                $filas[] = [
                    'id' => $anul->id,
                    'tipo' => 'Anulación',
                    'tipo_badge' => 'danger',
                    'codigo' => $anul->codigo,
                    'factura_codigo' => $anul->codigo,
                    'fecha_raw' => $fechaEvento ? $fechaEvento->toDateTimeString() : '',
                    'fecha' => $fechaEvento ? $fechaEvento->format('d/m/Y') : Carbon::parse($anul->fecha_emision)->format('d/m/Y'),
                    'hora' => $fechaEvento ? $fechaEvento->format('h:i A') : '',
                    'usuario' => $anul->anuladaPor?->name ?: ($anul->usuario?->name ?: 'Sistema'),
                    'cliente' => $anul->cliente?->nombre ?: 'Consumidor Final',
                    'motivo' => $anul->motivo_anulacion ?: 'Factura anulada',
                    'monto_usd' => $montoUsd,
                    'monto_bs' => $montoBs,
                    'metodo_reembolso' => 'Reversión Total',
                ];
            }
        }

        // Ordenar cronológicamente descendente
        usort($filas, fn ($a, $b) => strcmp($b['fecha_raw'], $a['fecha_raw']));

        $totalGeneralUsd = round($totalDevolucionesUsd + $totalAnuladasUsd, 2);
        $totalGeneralBs = round($totalDevolucionesBs + $totalAnuladasBs, 2);

        return [
            'kpis' => [
                'total_devoluciones_usd' => round($totalDevolucionesUsd, 2),
                'total_devoluciones_bs' => round($totalDevolucionesBs, 2),
                'total_anuladas_usd' => round($totalAnuladasUsd, 2),
                'total_anuladas_bs' => round($totalAnuladasBs, 2),
                'total_general_usd' => $totalGeneralUsd,
                'total_general_bs' => $totalGeneralBs,
                'conteo_devoluciones' => count(array_filter($filas, fn ($f) => $f['tipo'] === 'Devolución')),
                'conteo_anulaciones' => count(array_filter($filas, fn ($f) => $f['tipo'] === 'Anulación')),
                'conteo_total' => count($filas),
                'tasa_cambio' => $tasaCambio,
                'periodo_texto' => "Del {$fechaInicio->format('d/m/Y')} al {$fechaFin->format('d/m/Y')}",
            ],
            'items' => $filas,
        ];
    }

    /**
     * Reporte 9: Flujo de Caja Real por Método de Pago
     * Fórmula reportes.md: pagos de ventas + abonos CXC + ingresos - vuelto - reembolsos - egresos
     */
    public function reporteFlujoCaja(int $empresaId, array $filtros): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $tasaCambio = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);

        $fechaInicio = ! empty($filtros['fecha_inicio']) ? Carbon::parse($filtros['fecha_inicio'])->startOfDay() : Carbon::now()->startOfMonth();
        $fechaFin = ! empty($filtros['fecha_fin']) ? Carbon::parse($filtros['fecha_fin'])->endOfDay() : Carbon::now()->endOfDay();
        $cajaId = ! empty($filtros['caja_id']) ? (int) $filtros['caja_id'] : null;
        $cajaTurnoId = ! empty($filtros['caja_turno_id']) ? (int) $filtros['caja_turno_id'] : null;
        $metodoPagoId = ! empty($filtros['metodo_pago_id']) ? (int) $filtros['metodo_pago_id'] : null;

        // 1. ENTRADAS: Pagos de Ventas Válidas
        $queryPagosVentas = VentaPago::whereHas('venta', function ($q) use ($empresaId, $fechaInicio, $fechaFin, $cajaId, $cajaTurnoId) {
            $q->where('empresa_id', $empresaId)
                ->where('estado', '!=', 'anulada')
                ->whereBetween('fecha_emision', [$fechaInicio->toDateString(), $fechaFin->toDateString()]);

            if ($cajaId) {
                $q->where('caja_id', $cajaId);
            }
            if ($cajaTurnoId) {
                $q->where('caja_turno_id', $cajaTurnoId);
            }
        })->with(['metodoPago', 'venta']);

        if ($metodoPagoId) {
            $queryPagosVentas->where('metodo_pago_id', $metodoPagoId);
        }
        $pagosVentas = $queryPagosVentas->get();

        // 2. ENTRADAS: Abonos CXC
        $queryAbonosCxc = CuentaPorCobrarAbono::whereHas('cuentaPorCobrar', function ($q) use ($empresaId) {
            $q->where('empresa_id', $empresaId);
        })->whereBetween('fecha_abono', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->with(['metodoPago', 'cuentaPorCobrar.cliente']);

        if ($cajaTurnoId) {
            $queryAbonosCxc->where('caja_turno_id', $cajaTurnoId);
        }
        if ($metodoPagoId) {
            $queryAbonosCxc->where('metodo_pago_id', $metodoPagoId);
        }
        $abonosCxc = $queryAbonosCxc->get();

        // 3. MOVIMIENTOS DE CAJA (Ingresos manuales, egresos manuales, vueltos, reembolsos)
        $queryMovimientos = CajaMovimiento::where('empresa_id', $empresaId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->with(['metodoPago', 'usuario', 'cajaTurno.caja']);

        if ($cajaTurnoId) {
            $queryMovimientos->where('caja_turno_id', $cajaTurnoId);
        } elseif ($cajaId) {
            $queryMovimientos->whereHas('cajaTurno', fn ($q) => $q->where('caja_id', $cajaId));
        }
        if ($metodoPagoId) {
            $queryMovimientos->where('metodo_pago_id', $metodoPagoId);
        }
        $movimientos = $queryMovimientos->get();

        $movIngresos = $movimientos->where('tipo', 'ingreso');
        $movEgresos = $movimientos->where('tipo', 'egreso');

        // Desglose por Método de Pago
        $metodosCatalogo = MetodoPago::where('estado', true)->orderBy('nombre')->get();
        $desgloseMetodos = [];
        $totalEntradasUsd = 0.0;
        $totalEntradasBs = 0.0;
        $totalSalidasUsd = 0.0;
        $totalSalidasBs = 0.0;
        $totalVueltosUsd = 0.0;
        $totalReembolsosUsd = 0.0;

        foreach ($metodosCatalogo as $metodo) {
            if ($metodoPagoId && $metodo->id !== $metodoPagoId) {
                continue;
            }

            $pagoVentasMet = $pagosVentas->where('metodo_pago_id', $metodo->id);
            $abonosMet = $abonosCxc->where('metodo_pago_id', $metodo->id);
            $ingresosMet = $movIngresos->where('metodo_pago_id', $metodo->id);
            $egresosMet = $movEgresos->where('metodo_pago_id', $metodo->id);

            $entradasUsd = (float) $pagoVentasMet->sum('monto_usd') + (float) $abonosMet->sum('monto_usd') + (float) $ingresosMet->sum('monto_usd');
            $entradasBs = (float) $pagoVentasMet->sum('monto_bs') + (float) $abonosMet->sum('monto_bs') + (float) $ingresosMet->sum('monto_bs');

            $salidasUsd = (float) $egresosMet->sum('monto_usd');
            $salidasBs = (float) $egresosMet->sum('monto_bs');

            $netoUsd = round($entradasUsd - $salidasUsd, 2);
            $netoBs = round($entradasBs - $salidasBs, 2);

            $totalEntradasUsd += $entradasUsd;
            $totalEntradasBs += $entradasBs;
            $totalSalidasUsd += $salidasUsd;
            $totalSalidasBs += $salidasBs;

            $desgloseMetodos[] = [
                'metodo_id' => $metodo->id,
                'nombre' => $metodo->nombre,
                'es_efectivo' => (bool) $metodo->es_efectivo,
                'entradas_usd' => round($entradasUsd, 2),
                'entradas_bs' => round($entradasBs, 2),
                'salidas_usd' => round($salidasUsd, 2),
                'salidas_bs' => round($salidasBs, 2),
                'neto_usd' => $netoUsd,
                'neto_bs' => $netoBs,
            ];
        }

        // Vueltos y reembolsos
        $totalVueltosUsd = (float) $movEgresos->filter(fn ($m) => $m->documento_tipo === 'venta')->sum('monto_usd');
        $totalReembolsosUsd = (float) $movEgresos->filter(fn ($m) => in_array($m->documento_tipo, ['devolucion', 'devolucion_venta', 'anulacion', 'anulacion_venta'], true))->sum('monto_usd');

        // Mapeo detallado de transacciones
        $transacciones = [];
        foreach ($pagosVentas as $p) {
            $transacciones[] = [
                'fecha_raw' => $p->created_at ? $p->created_at->toDateTimeString() : '',
                'fecha' => $p->created_at ? $p->created_at->format('d/m/Y') : Carbon::parse($p->venta?->fecha_emision)->format('d/m/Y'),
                'tipo_flujo' => 'Entrada',
                'concepto' => "Cobro de Venta #{$p->venta?->codigo}",
                'metodo' => $p->metodoPago?->nombre ?: 'Efectivo',
                'monto_usd' => (float) $p->monto_usd,
                'monto_bs' => (float) $p->monto_bs,
            ];
        }
        foreach ($abonosCxc as $ab) {
            $transacciones[] = [
                'fecha_raw' => $ab->created_at ? $ab->created_at->toDateTimeString() : '',
                'fecha' => Carbon::parse($ab->fecha_abono)->format('d/m/Y'),
                'tipo_flujo' => 'Entrada',
                'concepto' => "Abono CXC Cl: {$ab->cuentaPorCobrar?->cliente?->nombre}",
                'metodo' => $ab->metodoPago?->nombre ?: 'Efectivo',
                'monto_usd' => (float) $ab->monto_usd,
                'monto_bs' => (float) $ab->monto_bs,
            ];
        }
        foreach ($movimientos as $mov) {
            $esSalida = $mov->tipo === 'egreso';
            $transacciones[] = [
                'fecha_raw' => $mov->created_at ? $mov->created_at->toDateTimeString() : '',
                'fecha' => $mov->created_at ? $mov->created_at->format('d/m/Y') : '',
                'tipo_flujo' => $esSalida ? 'Salida' : 'Entrada',
                'concepto' => $mov->concepto ?: ($esSalida ? 'Egreso de Caja' : 'Ingreso de Caja'),
                'metodo' => $mov->metodoPago?->nombre ?: 'Efectivo',
                'monto_usd' => ($esSalida ? -1 : 1) * (float) $mov->monto_usd,
                'monto_bs' => ($esSalida ? -1 : 1) * (float) $mov->monto_bs,
            ];
        }

        usort($transacciones, fn ($a, $b) => strcmp($b['fecha_raw'], $a['fecha_raw']));

        $flujoNetoUsd = round($totalEntradasUsd - $totalSalidasUsd, 2);
        $flujoNetoBs = round($totalEntradasBs - $totalSalidasBs, 2);

        return [
            'kpis' => [
                'total_entradas_usd' => round($totalEntradasUsd, 2),
                'total_entradas_bs' => round($totalEntradasBs, 2),
                'total_salidas_usd' => round($totalSalidasUsd, 2),
                'total_salidas_bs' => round($totalSalidasBs, 2),
                'flujo_neto_usd' => $flujoNetoUsd,
                'flujo_neto_bs' => $flujoNetoBs,
                'total_vueltos_usd' => round($totalVueltosUsd, 2),
                'total_reembolsos_usd' => round($totalReembolsosUsd, 2),
                'tasa_cambio' => $tasaCambio,
                'periodo_texto' => "Del {$fechaInicio->format('d/m/Y')} al {$fechaFin->format('d/m/Y')}",
            ],
            'desglose_metodos' => $desgloseMetodos,
            'transacciones' => $transacciones,
        ];
    }

    /**
     * Reporte 10: Libro de Ventas (Formato Fiscal sin IGTF)
     */
    public function reporteLibroVentas(int $empresaId, array $filtros): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $tasaCambio = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);

        $fechaInicio = ! empty($filtros['fecha_inicio']) ? Carbon::parse($filtros['fecha_inicio'])->startOfDay() : Carbon::now()->startOfMonth();
        $fechaFin = ! empty($filtros['fecha_fin']) ? Carbon::parse($filtros['fecha_fin'])->endOfDay() : Carbon::now()->endOfDay();

        $ventas = Venta::with(['cliente', 'detalles'])
            ->where('empresa_id', $empresaId)
            ->whereBetween('fecha_emision', [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->orderBy('fecha_emision')
            ->orderBy('id')
            ->get();

        $devoluciones = DevolucionVenta::with(['venta.cliente', 'detalles'])
            ->where('empresa_id', $empresaId)
            ->whereBetween(DB::raw('DATE(created_at)'), [$fechaInicio->toDateString(), $fechaFin->toDateString()])
            ->orderBy('id')
            ->get();

        $filas = [];
        $totalFacturadoUsd = 0.0;
        $totalFacturadoBs = 0.0;
        $totalBaseUsd = 0.0;
        $totalBaseBs = 0.0;
        $totalIvaUsd = 0.0;
        $totalIvaBs = 0.0;
        $totalExentoUsd = 0.0;
        $totalExentoBs = 0.0;

        foreach ($ventas as $v) {
            $esAnulada = $v->estado === 'anulada';

            $detallesExentos = $v->detalles->filter(fn ($d) => ! $d->aplica_iva || (float) $d->iva_porcentaje <= 0);
            $detallesGravados = $v->detalles->filter(fn ($d) => $d->aplica_iva && (float) $d->iva_porcentaje > 0);

            $exentoUsd = $esAnulada ? 0.0 : round((float) $detallesExentos->sum('subtotal_usd'), 2);
            $exentoBs = $esAnulada ? 0.0 : round((float) $detallesExentos->sum('subtotal_bs'), 2);

            $baseUsd = $esAnulada ? 0.0 : round((float) $detallesGravados->sum('subtotal_usd'), 2);
            $baseBs = $esAnulada ? 0.0 : round((float) $detallesGravados->sum('subtotal_bs'), 2);

            $ivaUsd = $esAnulada ? 0.0 : round((float) $v->iva_monto_usd, 2);
            $ivaBs = $esAnulada ? 0.0 : round((float) $v->iva_monto_bs, 2);

            $totalUsd = $esAnulada ? 0.0 : round((float) $v->total_usd, 2);
            $totalBs = $esAnulada ? 0.0 : round((float) $v->total_bs, 2);

            if (! $esAnulada) {
                $totalFacturadoUsd += $totalUsd;
                $totalFacturadoBs += $totalBs;
                $totalBaseUsd += $baseUsd;
                $totalBaseBs += $baseBs;
                $totalIvaUsd += $ivaUsd;
                $totalIvaBs += $ivaBs;
                $totalExentoUsd += $exentoUsd;
                $totalExentoBs += $exentoBs;
            }

            $filas[] = [
                'fecha' => Carbon::parse($v->fecha_emision)->format('d/m/Y'),
                'fecha_raw' => $v->fecha_emision ? Carbon::parse($v->fecha_emision)->toDateString() : '',
                'tipo_doc' => 'Factura',
                'codigo' => $v->codigo,
                'numero_control' => $v->numero_control ?: 'N/A',
                'factura_afectada' => '',
                'cliente_nombre' => $v->cliente?->nombre ?: 'Consumidor Final',
                'cliente_rif' => $v->cliente?->cedula ?: 'V-00000000',
                'total_usd' => $totalUsd,
                'total_bs' => $totalBs,
                'exento_usd' => $exentoUsd,
                'exento_bs' => $exentoBs,
                'base_imponible_usd' => $baseUsd,
                'base_imponible_bs' => $baseBs,
                'alicuota_iva' => '16.00%',
                'iva_usd' => $ivaUsd,
                'iva_bs' => $ivaBs,
                'estado' => $esAnulada ? 'ANULADA' : 'VÁLIDA',
            ];
        }

        foreach ($devoluciones as $dev) {
            $montoDevUsd = (float) $dev->total_devuelto_usd;
            $montoDevBs = (float) $dev->total_devuelto_bs;

            $baseDevUsd = round((float) $dev->detalles->sum('subtotal_usd'), 2);
            $baseDevBs = round((float) $dev->detalles->sum('subtotal_bs'), 2);
            $ivaDevUsd = max(0.0, round($montoDevUsd - $baseDevUsd, 2));
            $ivaDevBs = max(0.0, round($montoDevBs - $baseDevBs, 2));

            // Las devoluciones en el libro restan fiscalmente
            $totalFacturadoUsd -= $montoDevUsd;
            $totalFacturadoBs -= $montoDevBs;
            $totalBaseUsd -= $baseDevUsd;
            $totalBaseBs -= $baseDevBs;
            $totalIvaUsd -= $ivaDevUsd;
            $totalIvaBs -= $ivaDevBs;

            $filas[] = [
                'fecha' => $dev->created_at ? $dev->created_at->format('d/m/Y') : '',
                'fecha_raw' => $dev->created_at ? $dev->created_at->toDateString() : '',
                'tipo_doc' => 'Nota de Crédito (Dev.)',
                'codigo' => $dev->codigo,
                'numero_control' => 'N/A',
                'factura_afectada' => $dev->venta?->codigo ?: 'N/A',
                'cliente_nombre' => $dev->venta?->cliente?->nombre ?: 'Consumidor Final',
                'cliente_rif' => $dev->venta?->cliente?->cedula ?: 'V-00000000',
                'total_usd' => -$montoDevUsd,
                'total_bs' => -$montoDevBs,
                'exento_usd' => 0.0,
                'exento_bs' => 0.0,
                'base_imponible_usd' => -$baseDevUsd,
                'base_imponible_bs' => -$baseDevBs,
                'alicuota_iva' => '16.00%',
                'iva_usd' => -$ivaDevUsd,
                'iva_bs' => -$ivaDevBs,
                'estado' => 'DEVUELTA',
            ];
        }

        return [
            'kpis' => [
                'total_facturado_usd' => round($totalFacturadoUsd, 2),
                'total_facturado_bs' => round($totalFacturadoBs, 2),
                'total_base_imponible_usd' => round($totalBaseUsd, 2),
                'total_base_imponible_bs' => round($totalBaseBs, 2),
                'total_iva_usd' => round($totalIvaUsd, 2),
                'total_iva_bs' => round($totalIvaBs, 2),
                'total_exento_usd' => round($totalExentoUsd, 2),
                'total_exento_bs' => round($totalExentoBs, 2),
                'conteo_facturas' => $ventas->count(),
                'conteo_devoluciones' => $devoluciones->count(),
                'tasa_cambio' => $tasaCambio,
                'periodo_texto' => "Del {$fechaInicio->format('d/m/Y')} al {$fechaFin->format('d/m/Y')}",
            ],
            'items' => $filas,
        ];
    }
}
