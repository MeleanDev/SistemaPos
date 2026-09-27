<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Vendedor;
use App\Models\Venta;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReporteVendedorController extends Controller
{
    public function index(): View
    {
        $empresaId = $this->obtenerEmpresaId();
        $vendedores = Vendedor::where('empresa_id', $empresaId)->where('estado', true)->orderBy('nombre')->get();

        return view('Sistema.pages.empresa.reporte-vendedores', compact('vendedores'));
    }

    public function kpis(Request $request): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $query = Venta::where('empresa_id', $empresaId)
                ->whereNotNull('vendedor_id')
                ->where('estado', '!=', 'anulada');

            if ($fechaInicio = $request->input('fecha_inicio')) {
                $query->where('fecha_emision', '>=', $fechaInicio);
            }
            if ($fechaFin = $request->input('fecha_fin')) {
                $query->where('fecha_emision', '<=', $fechaFin);
            }
            if ($vendedorId = $request->input('vendedor_id')) {
                $query->where('vendedor_id', $vendedorId);
            }

            $ventas = $query->get();

            $totalVentasUsd = (float) $ventas->sum('total_usd');
            $totalVentasBs = (float) $ventas->sum('total_bs');
            $totalComisionesUsd = (float) $ventas->sum('comision_monto_usd');
            $totalComisionesBs = (float) $ventas->sum('comision_monto_bs');
            $conteoFacturas = $ventas->count();

            // Vendedor Top
            $topGroup = $ventas->groupBy('vendedor_id')->map(function ($group) {
                return [
                    'total_usd' => $group->sum('total_usd'),
                    'vendedor_id' => $group->first()->vendedor_id,
                ];
            })->sortByDesc('total_usd')->first();

            $topVendedorNombre = 'N/A';
            if ($topGroup) {
                $vendedorObj = Vendedor::find($topGroup['vendedor_id']);
                if ($vendedorObj) {
                    $topVendedorNombre = "{$vendedorObj->nombre} ($".number_format($topGroup['total_usd'], 2).')';
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_ventas_usd' => round($totalVentasUsd, 2),
                    'total_ventas_bs' => round($totalVentasBs, 2),
                    'total_comisiones_usd' => round($totalComisionesUsd, 2),
                    'total_comisiones_bs' => round($totalComisionesBs, 2),
                    'conteo_facturas' => $conteoFacturas,
                    'top_vendedor' => $topVendedorNombre,
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
        $empresaId = $this->obtenerEmpresaId();

        $query = Venta::with(['vendedor', 'cliente', 'almacen'])
            ->where('empresa_id', $empresaId)
            ->whereNotNull('vendedor_id')
            ->where('estado', '!=', 'anulada')
            ->orderBy('id', 'desc');

        if ($fechaInicio = $request->input('fecha_inicio')) {
            $query->where('fecha_emision', '>=', $fechaInicio);
        }
        if ($fechaFin = $request->input('fecha_fin')) {
            $query->where('fecha_emision', '<=', $fechaFin);
        }
        if ($vendedorId = $request->input('vendedor_id')) {
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
        $empresaId = $this->obtenerEmpresaId();

        $vendedores = Vendedor::where('empresa_id', $empresaId)->get();

        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        $resumen = $vendedores->map(function ($v) use ($fechaInicio, $fechaFin) {
            $ventasQuery = $v->ventas()->where('estado', '!=', 'anulada');

            if ($fechaInicio) {
                $ventasQuery->where('fecha_emision', '>=', $fechaInicio);
            }
            if ($fechaFin) {
                $ventasQuery->where('fecha_emision', '<=', $fechaFin);
            }

            $ventas = $ventasQuery->get();

            return [
                'id' => $v->id,
                'nombre' => $v->nombre,
                'documento' => $v->documento_completo,
                'comision_porcentaje' => (float) $v->comision_porcentaje,
                'conteo_ventas' => $ventas->count(),
                'total_vendido_usd' => (float) $ventas->sum('total_usd'),
                'total_vendido_bs' => (float) $ventas->sum('total_bs'),
                'total_comision_usd' => (float) $ventas->sum('comision_monto_usd'),
                'total_comision_bs' => (float) $ventas->sum('comision_monto_bs'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $resumen,
        ]);
    }
}
