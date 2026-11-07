<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Vendedor;
use App\Models\Venta;
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

            $query = Venta::where('empresa_id', $empresaId)
                ->whereNotNull('vendedor_id')
                ->whereNotIn('estado', ['anulada', 'devuelta_total']);

            if ($fechaInicio = $request->input('fecha_inicio')) {
                $query->where('fecha_emision', '>=', $fechaInicio);
            }
            if ($fechaFin = $request->input('fecha_fin')) {
                $query->where('fecha_emision', '<=', $fechaFin);
            }

            if (! $esAdmin && $vendedorPropio) {
                $query->where('vendedor_id', $vendedorPropio->id);
            } elseif ($vendedorId = $request->input('vendedor_id')) {
                $query->where('vendedor_id', $vendedorId);
            }

            $totales = (clone $query)->selectRaw('
                COALESCE(SUM(total_usd), 0) as total_ventas_usd,
                COALESCE(SUM(total_bs), 0) as total_ventas_bs,
                COALESCE(SUM(comision_monto_usd), 0) as total_comisiones_usd,
                COALESCE(SUM(comision_monto_bs), 0) as total_comisiones_bs,
                COUNT(*) as conteo_facturas
            ')->first();

            $topGroup = (clone $query)
                ->selectRaw('vendedor_id, SUM(total_usd) as total_usd, COUNT(*) as conteo_facturas')
                ->groupBy('vendedor_id')
                ->orderByDesc('total_usd')
                ->first();

            $topVendedorNombre = 'N/A';
            $topVendedorMontoUsd = 0.00;
            if ($topGroup) {
                $vendedorObj = Vendedor::find($topGroup->vendedor_id);
                if ($vendedorObj) {
                    $topVendedorMontoUsd = round((float) $topGroup->total_usd, 2);
                    $topVendedorNombre = "{$vendedorObj->nombre} ($".number_format($topVendedorMontoUsd, 2).')';
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_ventas_usd' => round((float) $totales->total_ventas_usd, 2),
                    'total_ventas_bs' => round((float) $totales->total_ventas_bs, 2),
                    'total_comisiones_usd' => round((float) $totales->total_comisiones_usd, 2),
                    'total_comisiones_bs' => round((float) $totales->total_comisiones_bs, 2),
                    'conteo_facturas' => (int) $totales->conteo_facturas,
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

        $query = Venta::with(['vendedor', 'cliente', 'almacen'])
            ->where('empresa_id', $empresaId)
            ->whereNotNull('vendedor_id')
            ->whereNotIn('estado', ['anulada', 'devuelta_total'])
            ->orderBy('id', 'desc');

        if ($fechaInicio = $request->input('fecha_inicio')) {
            $query->where('fecha_emision', '>=', $fechaInicio);
        }
        if ($fechaFin = $request->input('fecha_fin')) {
            $query->where('fecha_emision', '<=', $fechaFin);
        }

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

        $ventasQuery = Venta::where('empresa_id', $empresaId)
            ->whereNotNull('vendedor_id')
            ->whereNotIn('estado', ['anulada', 'devuelta_total']);

        if ($fechaInicio) {
            $ventasQuery->where('fecha_emision', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $ventasQuery->where('fecha_emision', '<=', $fechaFin);
        }

        if (! $esAdmin && $vendedorPropio) {
            $ventasQuery->where('vendedor_id', $vendedorPropio->id);
        } elseif ($vendedorId) {
            $ventasQuery->where('vendedor_id', $vendedorId);
        }

        $ventasAgrupadas = $ventasQuery
            ->selectRaw('
                vendedor_id,
                COUNT(*) as conteo_ventas,
                SUM(total_usd) as total_vendido_usd,
                SUM(total_bs) as total_vendido_bs,
                SUM(comision_monto_usd) as total_comision_usd,
                SUM(comision_monto_bs) as total_comision_bs
            ')
            ->groupBy('vendedor_id')
            ->get()
            ->keyBy('vendedor_id');

        $resumen = $vendedores->map(function ($v) use ($ventasAgrupadas) {
            $agrupado = $ventasAgrupadas->get($v->id);

            return [
                'id' => $v->id,
                'nombre' => $v->nombre,
                'documento' => $v->documento_completo,
                'telefono' => $v->telefono ?? 'N/A',
                'correo' => $v->correo ?? 'N/A',
                'comision_porcentaje' => (float) $v->comision_porcentaje,
                'conteo_ventas' => $agrupado ? (int) $agrupado->conteo_ventas : 0,
                'total_vendido_usd' => $agrupado ? round((float) $agrupado->total_vendido_usd, 2) : 0.00,
                'total_vendido_bs' => $agrupado ? round((float) $agrupado->total_vendido_bs, 2) : 0.00,
                'total_comision_usd' => $agrupado ? round((float) $agrupado->total_comision_usd, 2) : 0.00,
                'total_comision_bs' => $agrupado ? round((float) $agrupado->total_comision_bs, 2) : 0.00,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $resumen,
        ]);
    }
}
