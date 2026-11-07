<?php

namespace App\Service\Empresa;

use App\Models\Caja;
use App\Models\CajaTurno;
use App\Models\Cliente;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorPagar;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaPago;
use App\Queries\VentaNetaQuery;
use Carbon\Carbon;

class PanelPrincipalClass
{
    /**
     * Obtiene todas las métricas operativas y financieras del Dashboard
     * filtradas estrictamente según los permisos del usuario y la empresa activa.
     */
    public function obtenerDatosDashboard(int $empresaId, User $user): array
    {
        $empresa = Empresa::findOrFail($empresaId);
        $tasaUsd = (float) ($empresa->tasa_usd > 0 ? $empresa->tasa_usd : 1);

        $permisos = [
            'ventas_ver' => $user->can('ventas.ver'),
            'ventas_crear' => $user->can('ventas.crear'),
            'pos_acceso' => $user->can('pos.acceso'),
            'productos_ver' => $user->can('productos.ver'),
            'productos_crear' => $user->can('productos.crear'),
            'clientes_ver' => $user->can('clientes.ver'),
            'clientes_crear' => $user->can('clientes.crear'),
            'cajas_ver' => $user->can('cajas.ver'),
            'cajas_aperturar' => $user->can('cajas.aperturar'),
            'cajas_cerrar' => $user->can('cajas.cerrar'),
            'cxc_ver' => $user->can('cxc.ver'),
            'cxp_ver' => $user->can('cxp.ver'),
            'compras_recepcion' => $user->can('compras.recepcion'),
            'reportes_ver' => $user->can('reportes.ver'),
        ];

        $datos = [
            'empresa' => [
                'id' => $empresa->id,
                'nombre' => $empresa->nombre,
                'nombre_comercial' => $empresa->nombre,
                'razon_social' => $empresa->razon_social,
                'rif' => $empresa->rif,
                'tasa_usd' => $tasaUsd,
                'moneda_principal' => $empresa->moneda_principal ?? 'USD',
                'maneja_motos' => (bool) $empresa->maneja_motos,
            ],
            'usuario' => [
                'id' => $user->id,
                'name' => $user->name,
                'nombre_completo' => $user->nombre_completo,
                'rol_principal' => $user->roles->first()?->name ?? 'Usuario',
            ],
            'permisos' => $permisos,
            'kpis' => [],
            'grafico_ventas' => null,
            'caja_estado' => null,
            'transacciones_recientes' => [],
            'alertas_stock' => [],
        ];

        // 1. Métricas de Ventas y Facturación
        if ($permisos['ventas_ver']) {
            $hoy = Carbon::today();
            $ayer = Carbon::yesterday();

            $queryVentasHoy = VentaNetaQuery::validas($empresaId)
                ->whereDate('fecha_emision', $hoy);

            $metricasHoy = VentaNetaQuery::calcularTotales($queryVentasHoy);
            $ventasHoyUsd = $metricasHoy['total_neto_usd'];
            $ventasHoyBs = $metricasHoy['total_neto_bs'];
            $facturasHoyCount = $metricasHoy['conteo_ventas'];
            $ticketPromedioUsd = $metricasHoy['ticket_promedio_usd'];

            $queryVentasAyer = VentaNetaQuery::validas($empresaId)
                ->whereDate('fecha_emision', $ayer);
            $metricasAyer = VentaNetaQuery::calcularTotales($queryVentasAyer);
            $ventasAyerUsd = $metricasAyer['total_neto_usd'];

            $crecimientoVentas = 0;
            if ($ventasAyerUsd > 0) {
                $crecimientoVentas = round((($ventasHoyUsd - $ventasAyerUsd) / $ventasAyerUsd) * 100, 1);
            } elseif ($ventasHoyUsd > 0) {
                $crecimientoVentas = 100;
            }

            $datos['kpis']['ventas_hoy_usd'] = $ventasHoyUsd;
            $datos['kpis']['ventas_hoy_bs'] = $ventasHoyBs;
            $datos['kpis']['facturas_hoy_count'] = $facturasHoyCount;
            $datos['kpis']['ticket_promedio_usd'] = $ticketPromedioUsd;
            $datos['kpis']['crecimiento_ventas'] = $crecimientoVentas;

            // Histórico 7 días para el gráfico
            $graficoLabels = [];
            $graficoDataUsd = [];
            $graficoDataBs = [];
            $graficoDataCant = [];

            $nombresDias = [
                0 => 'Dom',
                1 => 'Lun',
                2 => 'Mar',
                3 => 'Mié',
                4 => 'Jue',
                5 => 'Vie',
                6 => 'Sáb',
            ];

            for ($i = 6; $i >= 0; $i--) {
                $fecha = Carbon::today()->subDays($i);
                $fechaStr = $fecha->toDateString();
                $nombreDia = $nombresDias[$fecha->dayOfWeek] ?? $fecha->format('D');
                $etiqueta = $nombreDia.' '.$fecha->format('d/m');

                $queryDia = VentaNetaQuery::validas($empresaId)
                    ->whereDate('fecha_emision', $fechaStr);
                $metricasDia = VentaNetaQuery::calcularTotales($queryDia);

                $graficoLabels[] = $etiqueta;
                $graficoDataUsd[] = $metricasDia['total_neto_usd'];
                $graficoDataBs[] = $metricasDia['total_neto_bs'];
                $graficoDataCant[] = $metricasDia['conteo_ventas'];
            }

            $datos['grafico_ventas'] = [
                'labels' => $graficoLabels,
                'totales_usd' => $graficoDataUsd,
                'totales_bs' => $graficoDataBs,
                'cantidades' => $graficoDataCant,
            ];

            // Transacciones Recientes (últimas 6)
            $transacciones = VentaNetaQuery::conVentaNeta(VentaNetaQuery::validas($empresaId))
                ->with(['cliente', 'usuario', 'pagos.metodoPago'])
                ->latest('id')
                ->take(6)
                ->get();

            $datos['transacciones_recientes'] = $transacciones->map(function (Venta $v) {
                $metodosNombres = $v->pagos->map(fn ($p) => $p->metodoPago?->nombre ?? 'N/A')->unique()->implode(', ');
                if ($v->saldo_pendiente_usd > 0) {
                    $metodosNombres .= ($metodosNombres ? ', ' : '').'Crédito';
                }

                return [
                    'id' => $v->id,
                    'codigo' => $v->codigo,
                    'numero_control' => $v->numero_control ?: 'N/A',
                    'cliente' => $v->cliente?->nombre ?: 'Consumidor Final',
                    'cajero' => $v->usuario?->name ?: 'Sistema',
                    'metodos' => $metodosNombres ?: 'Efectivo',
                    'total_usd' => (float) $v->total_usd,
                    'total_bs' => (float) $v->total_bs,
                    'venta_neta_usd' => (float) ($v->venta_neta_usd ?? $v->total_usd),
                    'fecha_hora' => $v->created_at ? $v->created_at->format('d/m/Y h:i A') : Carbon::parse($v->fecha_emision)->format('d/m/Y'),
                    'estado' => $v->estado,
                ];
            })->values()->toArray();
        }

        // 2. Métricas de Catálogo y Productos
        if ($permisos['productos_ver']) {
            $totalProductos = Producto::where('empresa_id', $empresaId)
                ->where('estado', true)
                ->where('tipo', '!=', 'servicio')
                ->count();

            $totalServicios = Producto::where('empresa_id', $empresaId)
                ->where('estado', true)
                ->where('tipo', 'servicio')
                ->count();

            // Productos con stock bajo o agotado
            $queryBajoStock = Producto::where('empresa_id', $empresaId)
                ->where('estado', true)
                ->where('tipo', '!=', 'servicio')
                ->where('stock_minimo', '>', 0)
                ->whereRaw('COALESCE((SELECT SUM(cantidad_actual) FROM producto_stock_almacenes WHERE producto_stock_almacenes.producto_id = productos.id), 0) <= productos.stock_minimo');

            $conteoBajoStock = (clone $queryBajoStock)->count();

            $alertasStock = (clone $queryBajoStock)
                ->withSum('stockAlmacenes as stock_actual', 'cantidad_actual')
                ->orderBy('stock_actual', 'asc')
                ->take(5)
                ->get(['id', 'codigo_interno', 'nombre', 'stock_minimo', 'unidad_medida', 'precio_detal_usd']);

            $datos['kpis']['total_productos'] = $totalProductos;
            $datos['kpis']['total_servicios'] = $totalServicios;
            $datos['kpis']['productos_bajo_stock_count'] = $conteoBajoStock;

            $datos['alertas_stock'] = $alertasStock->map(function ($p) {
                return [
                    'id' => $p->id,
                    'codigo' => $p->codigo_interno,
                    'nombre' => $p->nombre,
                    'stock_minimo' => (float) $p->stock_minimo,
                    'stock_actual' => (float) ($p->stock_actual ?? 0),
                    'unidad_medida' => $p->unidad_medida ?? 'UND',
                    'precio_usd' => (float) $p->precio_detal_usd,
                ];
            })->toArray();
        }

        // 3. Métricas de Clientes
        if ($permisos['clientes_ver']) {
            $totalClientes = Cliente::where('estado', true)->count();
            $clientesNuevosMes = Cliente::where('estado', true)
                ->where('created_at', '>=', Carbon::now()->startOfMonth())
                ->count();

            $datos['kpis']['total_clientes'] = $totalClientes;
            $datos['kpis']['clientes_nuevos_mes'] = $clientesNuevosMes;
        }

        // 4. Métricas de Créditos: Cuentas por Cobrar (CXC)
        if ($permisos['cxc_ver']) {
            $cxcQuery = CuentaPorCobrar::where('empresa_id', $empresaId)
                ->whereIn('estado', ['pendiente', 'parcial']);

            $datos['kpis']['cxc_pendiente_usd'] = round((float) (clone $cxcQuery)->sum('saldo_pendiente_usd'), 2);
            $datos['kpis']['cxc_pendiente_bs'] = round((float) (clone $cxcQuery)->sum('saldo_pendiente_bs'), 2);
            $datos['kpis']['cxc_conteo'] = (clone $cxcQuery)->count();
        }

        // 5. Métricas de Finanzas: Cuentas por Pagar (CXP)
        if ($permisos['cxp_ver']) {
            $cxpQuery = CuentaPorPagar::where('empresa_id', $empresaId)
                ->whereIn('estado', ['pendiente', 'parcial']);

            $datos['kpis']['cxp_pendiente_usd'] = round((float) (clone $cxpQuery)->sum('saldo_pendiente_usd'), 2);
            $datos['kpis']['cxp_pendiente_bs'] = round((float) (clone $cxpQuery)->sum('saldo_pendiente_bs'), 2);
            $datos['kpis']['cxp_conteo'] = (clone $cxpQuery)->count();
        }

        // 6. Estado de Caja y Turnos
        if ($permisos['cajas_ver']) {
            $puedeVerTodosLosTurnos = $user->hasAnyRole(['SuperAdmin', 'Admin']) || $user->can('cajas.aperturar');

            // Priorizar turno activo asignado a este usuario si es cajero/operador, o cualquier turno abierto de la empresa
            $turnoActivo = CajaTurno::with(['caja', 'usuario'])
                ->where('empresa_id', $empresaId)
                ->where('estado', 'abierta')
                ->when(! $puedeVerTodosLosTurnos, function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })
                ->latest('id')
                ->first();

            // Si tiene permiso de apertura o es admin y no tiene turno propio, buscar el último turno abierto general
            if (! $turnoActivo && $puedeVerTodosLosTurnos) {
                $turnoActivo = CajaTurno::with(['caja', 'usuario'])
                    ->where('empresa_id', $empresaId)
                    ->where('estado', 'abierta')
                    ->latest('id')
                    ->first();
            }

            if ($turnoActivo) {
                $totalesTurno = VentaNetaQuery::calcularTotales($empresaId, null, null, null, (int) $turnoActivo->id);
                $ventasTotalUsd = $totalesTurno['neto_usd'];
                $ventasTotalBs = $totalesTurno['neto_bs'];

                $ventasTurnoIds = VentaNetaQuery::validas($empresaId)
                    ->where('caja_turno_id', $turnoActivo->id)
                    ->pluck('id');

                $pagosTurno = VentaPago::whereIn('venta_id', $ventasTurnoIds)
                    ->with('metodoPago')
                    ->get();

                $pagosEfectivoUsd = 0;
                $pagosDigitalesUsd = 0;

                foreach ($pagosTurno as $pago) {
                    if ($pago->metodoPago?->es_efectivo) {
                        $pagosEfectivoUsd += (float) $pago->monto_usd;
                    } else {
                        $pagosDigitalesUsd += (float) $pago->monto_usd;
                    }
                }

                $montoAperturaUsd = (float) $turnoActivo->monto_apertura_usd;
                $montoAperturaBs = (float) $turnoActivo->monto_apertura_bs;
                $reporteTurno = (new CajaClass)->calcularReporteTurno((int) $turnoActivo->id, $empresaId);
                $totalEnCajaUsd = round((float) ($reporteTurno['efectivo_esperado_usd'] ?? ($montoAperturaUsd + $pagosEfectivoUsd)), 2);

                $datos['caja_estado'] = [
                    'tiene_turno_abierto' => true,
                    'turno_id' => $turnoActivo->id,
                    'caja_id' => $turnoActivo->caja_id,
                    'caja_nombre' => $turnoActivo->caja?->nombre ?? 'Caja Principal',
                    'cajero_nombre' => $turnoActivo->usuario?->name ?? 'Operador',
                    'fecha_apertura' => Carbon::parse($turnoActivo->fecha_apertura)->format('d/m/Y'),
                    'hora_apertura' => $turnoActivo->hora_apertura ? Carbon::parse($turnoActivo->hora_apertura)->format('h:i A') : '',
                    'monto_apertura_usd' => $montoAperturaUsd,
                    'monto_apertura_bs' => $montoAperturaBs,
                    'ventas_efectivo_usd' => round($pagosEfectivoUsd, 2),
                    'pagos_digitales_usd' => round($pagosDigitalesUsd, 2),
                    'ventas_total_usd' => $ventasTotalUsd,
                    'ventas_total_bs' => $ventasTotalBs,
                    'total_en_caja_usd' => $totalEnCajaUsd,
                ];
            } else {
                $totalCajas = Caja::where('empresa_id', $empresaId)->where('estado', true)->count();
                $datos['caja_estado'] = [
                    'tiene_turno_abierto' => false,
                    'total_cajas' => $totalCajas,
                ];
            }
        }

        return $datos;
    }
}
