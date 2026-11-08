<?php

namespace App\Queries;

use App\Models\DevolucionVenta;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class VentaNetaQuery
{
    /**
     * Retorna una consulta base de ventas válidas (excluyendo anuladas).
     */
    public static function validas(?int $empresaId = null): Builder
    {
        $query = Venta::query()->where('estado', '!=', 'anulada');

        if ($empresaId !== null && $empresaId > 0) {
            $query->where('empresa_id', $empresaId);
        }

        return $query;
    }

    /**
     * Retorna una consulta de ventas válidas dentro de un período de emisión.
     */
    public static function paraPeriodo(int $empresaId, ?string $fechaInicio = null, ?string $fechaFin = null): Builder
    {
        $query = self::validas($empresaId);

        if ($fechaInicio) {
            $query->where('fecha_emision', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->where('fecha_emision', '<=', $fechaFin);
        }

        return $query;
    }

    /**
     * Añade al Builder de Ventas las columnas calculadas de total devuelto y venta neta.
     */
    public static function conVentaNeta(Builder $query): Builder
    {
        return $query->select('ventas.*')
            ->selectRaw('COALESCE((SELECT SUM(d.total_devuelto_usd) FROM devoluciones_venta d WHERE d.venta_id = ventas.id), 0) as total_devuelto_usd')
            ->selectRaw('COALESCE((SELECT SUM(d.total_devuelto_bs) FROM devoluciones_venta d WHERE d.venta_id = ventas.id), 0) as total_devuelto_bs')
            ->selectRaw('(ventas.total_usd - COALESCE((SELECT SUM(d.total_devuelto_usd) FROM devoluciones_venta d WHERE d.venta_id = ventas.id), 0)) as venta_neta_usd')
            ->selectRaw('(ventas.total_bs - COALESCE((SELECT SUM(d.total_devuelto_bs) FROM devoluciones_venta d WHERE d.venta_id = ventas.id), 0)) as venta_neta_bs');
    }

    /**
     * Calcula los totales agregados de ventas válidas, devoluciones y ventas netas.
     * Soporta recibir una instancia de Builder o los parámetros tradicionales (empresaId, fechaInicio, fechaFin, etc.)
     */
    public static function calcularTotales(
        Builder|int $query,
        ?string $fechaInicio = null,
        ?string $fechaFin = null,
        ?int $vendedorId = null,
        ?int $cajaTurnoId = null
    ): array {
        if (is_int($query)) {
            $empresaId = $query;
            $builder = self::validas($empresaId);
            if ($fechaInicio) {
                $builder->whereDate('ventas.fecha_emision', '>=', $fechaInicio);
            }
            if ($fechaFin) {
                $builder->whereDate('ventas.fecha_emision', '<=', $fechaFin);
            }
            if ($vendedorId) {
                $builder->where('ventas.vendedor_id', $vendedorId);
            }
            if ($cajaTurnoId) {
                $builder->where('ventas.caja_turno_id', $cajaTurnoId);
            }
            $query = $builder;
        }

        $clonQuery = clone $query;

        $totales = $clonQuery->selectRaw('
            COALESCE(SUM(ventas.total_usd), 0) as total_bruto_usd,
            COALESCE(SUM(ventas.total_bs), 0) as total_bruto_bs,
            COALESCE(SUM(ventas.subtotal_neto_usd), 0) as subtotal_bruto_usd,
            COALESCE(SUM(ventas.subtotal_neto_bs), 0) as subtotal_bruto_bs,
            COALESCE(SUM(ventas.comision_monto_usd), 0) as total_comisiones_usd,
            COALESCE(SUM(ventas.comision_monto_bs), 0) as total_comisiones_bs,
            COUNT(*) as conteo_ventas
        ')->first();

        $ventaIds = (clone $query)->pluck('ventas.id');

        $totalDevueltoUsd = 0.0;
        $totalDevueltoBs = 0.0;
        if ($ventaIds->isNotEmpty()) {
            $devs = DevolucionVenta::whereIn('venta_id', $ventaIds)
                ->selectRaw('COALESCE(SUM(total_devuelto_usd), 0) as tot_usd, COALESCE(SUM(total_devuelto_bs), 0) as tot_bs')
                ->first();
            $totalDevueltoUsd = (float) ($devs->tot_usd ?? 0);
            $totalDevueltoBs = (float) ($devs->tot_bs ?? 0);
        }

        $brutoUsd = round((float) ($totales->total_bruto_usd ?? 0), 2);
        $brutoBs = round((float) ($totales->total_bruto_bs ?? 0), 2);
        $netoUsd = max(0.0, round($brutoUsd - $totalDevueltoUsd, 2));
        $netoBs = max(0.0, round($brutoBs - $totalDevueltoBs, 2));
        $conteo = (int) ($totales->conteo_ventas ?? 0);

        return [
            'total_bruto_usd' => $brutoUsd,
            'total_bruto_bs' => $brutoBs,
            'bruto_usd' => $brutoUsd,
            'bruto_bs' => $brutoBs,
            'total_devuelto_usd' => round($totalDevueltoUsd, 2),
            'total_devuelto_bs' => round($totalDevueltoBs, 2),
            'devuelto_usd' => round($totalDevueltoUsd, 2),
            'devuelto_bs' => round($totalDevueltoBs, 2),
            'total_neto_usd' => $netoUsd,
            'total_neto_bs' => $netoBs,
            'neto_usd' => $netoUsd,
            'neto_bs' => $netoBs,
            'subtotal_bruto_usd' => round((float) ($totales->subtotal_bruto_usd ?? 0), 2),
            'subtotal_bruto_bs' => round((float) ($totales->subtotal_bruto_bs ?? 0), 2),
            'total_comisiones_usd' => round((float) ($totales->total_comisiones_usd ?? 0), 2),
            'total_comisiones_bs' => round((float) ($totales->total_comisiones_bs ?? 0), 2),
            'conteo_ventas' => $conteo,
            'ticket_promedio_usd' => $conteo > 0 ? round($netoUsd / $conteo, 2) : 0.0,
        ];
    }

    /**
     * Calcula la venta neta desglosada por vendedor para un rango dado,
     * garantizando el Criterio 1: Σ vendedores + sin_vendedor = venta neta total.
     */
    public static function desglosePorVendedor(int $empresaId, ?string $fechaInicio = null, ?string $fechaFin = null): Collection
    {
        $ventas = self::conVentaNeta(self::paraPeriodo($empresaId, $fechaInicio, $fechaFin))
            ->with(['vendedor:id,nombre,comision_porcentaje'])
            ->get();

        return $ventas->groupBy(fn (Venta $v) => $v->vendedor_id ?? 0)
            ->map(function (Collection $grupo, int|string $vendedorId) {
                $primerVenta = $grupo->first();
                $vendedor = $primerVenta?->vendedor;

                return [
                    'vendedor_id' => $vendedorId === 0 ? null : (int) $vendedorId,
                    'nombre' => $vendedor ? $vendedor->nombre : 'Sin Vendedor Asignado',
                    'conteo_ventas' => $grupo->count(),
                    'total_bruto_usd' => round((float) $grupo->sum('total_usd'), 2),
                    'total_devuelto_usd' => round((float) $grupo->sum('total_devuelto_usd'), 2),
                    'total_neto_usd' => round((float) $grupo->sum('venta_neta_usd'), 2),
                    'total_neto_bs' => round((float) $grupo->sum('venta_neta_bs'), 2),
                    'total_comision_usd' => round((float) $grupo->sum('comision_monto_usd'), 2),
                    'total_comision_bs' => round((float) $grupo->sum('comision_monto_bs'), 2),
                ];
            })
            ->values();
    }
}
