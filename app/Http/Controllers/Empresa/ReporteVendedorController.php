<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Vendedor;
use App\Queries\VentaNetaQuery;
use App\Service\Inventario\ConsultorPrecioClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReporteVendedorController extends Controller
{
    public function __construct(
        private ConsultorPrecioClass $consultorPrecioClass
    ) {}

    private function verificarModuloVendedoresHabilitado(): int
    {
        $empresa = $this->obtenerEmpresaActiva() ?? Empresa::find($this->obtenerEmpresaId());
        if (! $empresa || ! $empresa->maneja_vendedores) {
            abort(403, 'El módulo de vendedores no está habilitado para esta empresa.');
        }

        return (int) $empresa->id;
    }

    public function index(): View
    {
        $this->verificarModuloVendedoresHabilitado();

        return view('Sistema.pages.empresa.reporte-vendedores');
    }

    public function catalogos(): JsonResponse
    {
        $empresaId = $this->verificarModuloVendedoresHabilitado();
        $user = Auth::user();
        $esAdmin = $user && $user->hasAnyRole(['SuperAdmin', 'Admin']);
        $vendedorPropio = $user?->vendedor()->where('empresa_id', $empresaId)->first();

        $queryVendedores = Vendedor::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('nombre');

        if (! $esAdmin && $vendedorPropio) {
            $queryVendedores->where('id', $vendedorPropio->id);
        }

        $vendedores = $queryVendedores->get(['id', 'nombre', 'comision_porcentaje']);

        return response()->json([
            'success' => true,
            'data' => [
                'vendedores' => $vendedores,
                'tasa_usd' => (float) $this->consultorPrecioClass->obtenerTasaActiva($empresaId),
            ],
        ]);
    }

    public function kpis(Request $request): JsonResponse
    {
        $empresaId = $this->verificarModuloVendedoresHabilitado();

        try {
            $user = Auth::user();
            $esAdmin = $user && $user->hasAnyRole(['SuperAdmin', 'Admin']);
            $vendedorPropio = $user?->vendedor()->where('empresa_id', $empresaId)->first();

            $query = VentaNetaQuery::conVentaNeta(
                VentaNetaQuery::paraPeriodo($empresaId, $request->input('fecha_inicio'), $request->input('fecha_fin'))
                    ->whereNotNull('vendedor_id')
            );

            if (! $esAdmin && $vendedorPropio) {
                $query->where('vendedor_id', $vendedorPropio->id);
            } elseif ($vendedorId = $request->input('vendedor_id')) {
                $query->where('vendedor_id', $vendedorId);
            }

            $ventas = $query->get();
            $totalesVentasUsd = round((float) $ventas->sum('venta_neta_usd'), 2);
            $totalesVentasBs = round((float) $ventas->sum('venta_neta_bs'), 2);
            $totalesComisionesUsd = round((float) $ventas->sum('comision_monto_usd'), 2);
            $totalesComisionesBs = round((float) $ventas->sum('comision_monto_bs'), 2);
            $ventasPagadas = $ventas->where('comision_estado', 'pagada');
            $ventasPendientes = $ventas->where('comision_estado', 'pendiente');
            $comisionPagadaUsd = round((float) $ventasPagadas->sum('comision_monto_usd'), 2);
            $comisionPagadaBs = round((float) $ventasPagadas->sum('comision_monto_bs'), 2);
            $comisionPendienteUsd = round((float) $ventasPendientes->sum('comision_monto_usd'), 2);
            $comisionPendienteBs = round((float) $ventasPendientes->sum('comision_monto_bs'), 2);
            $conteoFacturas = $ventas->count();

            $topVendedorNombre = 'N/A';
            $topVendedorMontoUsd = 0.00;
            if ($ventas->isNotEmpty()) {
                $agrupadoVendedores = $ventas->groupBy('vendedor_id')
                    ->map(fn ($g) => (float) $g->sum('venta_neta_usd'))
                    ->sortDesc();

                $topVendedorId = $agrupadoVendedores->keys()->first();
                $topMonto = $agrupadoVendedores->first();

                if ($topVendedorId && $topVendedor = Vendedor::find($topVendedorId)) {
                    $topVendedorMontoUsd = round((float) $topMonto, 2);
                    $topVendedorNombre = "{$topVendedor->nombre} ($".number_format($topVendedorMontoUsd, 2).')';
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_ventas_usd' => $totalesVentasUsd,
                    'total_ventas_bs' => $totalesVentasBs,
                    'total_comisiones_usd' => $totalesComisionesUsd,
                    'total_comisiones_bs' => $totalesComisionesBs,
                    'total_comisiones_pagadas_usd' => $comisionPagadaUsd,
                    'total_comisiones_pagadas_bs' => $comisionPagadaBs,
                    'total_comisiones_pendientes_usd' => $comisionPendienteUsd,
                    'total_comisiones_pendientes_bs' => $comisionPendienteBs,
                    'conteo_facturas' => $conteoFacturas,
                    'top_vendedor' => $topVendedorNombre,
                    'top_vendedor_monto_usd' => $topVendedorMontoUsd,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular KPIs: '.$e->getMessage(),
            ], 500);
        }
    }

    public function lista(Request $request): JsonResponse
    {
        $empresaId = $this->verificarModuloVendedoresHabilitado();
        $user = Auth::user();
        $esAdmin = $user && $user->hasAnyRole(['SuperAdmin', 'Admin']);
        $vendedorPropio = $user?->vendedor()->where('empresa_id', $empresaId)->first();

        $query = VentaNetaQuery::conVentaNeta(
            VentaNetaQuery::paraPeriodo($empresaId, $request->input('fecha_inicio'), $request->input('fecha_fin'))
                ->whereNotNull('vendedor_id')
        )->with(['vendedor', 'cliente', 'almacen'])
            ->orderBy('id', 'desc');

        if (! $esAdmin && $vendedorPropio) {
            $query->where('vendedor_id', $vendedorPropio->id);
        } elseif ($vendedorId = $request->input('vendedor_id')) {
            $query->where('vendedor_id', $vendedorId);
        }

        return datatables()->of($query)
            ->filter(function ($q) {
                if ($search = request('search.value')) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('codigo', 'LIKE', "%{$search}%")
                            ->orWhere('numero_control', 'LIKE', "%{$search}%")
                            ->orWhereHas('vendedor', fn ($v) => $v->where('nombre', 'LIKE', "%{$search}%"))
                            ->orWhereHas('cliente', fn ($c) => $c->where('nombre', 'LIKE', "%{$search}%")->orWhere('cedula', 'LIKE', "%{$search}%"));
                    });
                }
            })
            ->toJson();
    }

    public function resumenVendedores(Request $request): JsonResponse
    {
        $empresaId = $this->verificarModuloVendedoresHabilitado();
        $user = Auth::user();
        $esAdmin = $user && $user->hasAnyRole(['SuperAdmin', 'Admin']);
        $vendedorPropio = $user?->vendedor()->where('empresa_id', $empresaId)->first();

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $vendedorId = $request->input('vendedor_id');

        $vendedoresQuery = Vendedor::where('empresa_id', $empresaId);
        if (! $esAdmin && $vendedorPropio) {
            $vendedoresQuery->where('id', $vendedorPropio->id);
        } elseif ($vendedorId) {
            $vendedoresQuery->where('id', $vendedorId);
        }
        $vendedores = $vendedoresQuery->orderBy('nombre')->get();

        $ventasQuery = VentaNetaQuery::conVentaNeta(
            VentaNetaQuery::paraPeriodo($empresaId, $fechaInicio, $fechaFin)
                ->whereNotNull('vendedor_id')
        );

        if (! $esAdmin && $vendedorPropio) {
            $ventasQuery->where('vendedor_id', $vendedorPropio->id);
        } elseif ($vendedorId) {
            $ventasQuery->where('vendedor_id', $vendedorId);
        }

        $ventasColeccion = $ventasQuery->get();
        $ventasAgrupadas = $ventasColeccion->groupBy('vendedor_id');

        $resumen = $vendedores->map(function ($v) use ($ventasAgrupadas) {
            $grupo = $ventasAgrupadas->get($v->id);
            $pagadas = $grupo ? $grupo->where('comision_estado', 'pagada') : collect();
            $pendientes = $grupo ? $grupo->where('comision_estado', 'pendiente') : collect();

            return [
                'id' => $v->id,
                'nombre' => $v->nombre,
                'documento' => $v->documento_completo,
                'telefono' => $v->telefono ?? 'N/A',
                'correo' => $v->correo ?? 'N/A',
                'comision_porcentaje' => (float) $v->comision_porcentaje,
                'conteo_ventas' => $grupo ? $grupo->count() : 0,
                'total_vendido_usd' => $grupo ? round((float) $grupo->sum('venta_neta_usd'), 2) : 0.00,
                'total_vendido_bs' => $grupo ? round((float) $grupo->sum('venta_neta_bs'), 2) : 0.00,
                'total_comision_usd' => $grupo ? round((float) $grupo->sum('comision_monto_usd'), 2) : 0.00,
                'total_comision_bs' => $grupo ? round((float) $grupo->sum('comision_monto_bs'), 2) : 0.00,
                'comision_pagada_usd' => round((float) $pagadas->sum('comision_monto_usd'), 2),
                'comision_pagada_bs' => round((float) $pagadas->sum('comision_monto_bs'), 2),
                'comision_pendiente_usd' => round((float) $pendientes->sum('comision_monto_usd'), 2),
                'comision_pendiente_bs' => round((float) $pendientes->sum('comision_monto_bs'), 2),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $resumen,
        ]);
    }
}
