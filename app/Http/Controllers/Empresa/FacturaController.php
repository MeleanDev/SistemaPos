<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use App\Service\Ventas\VentaClass;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FacturaController extends Controller
{
    public function __construct(
        private VentaClass $ventaService
    ) {}

    public function index(): View
    {
        return view('Sistema.pages.empresa.facturas');
    }

    public function kpis(): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $hoy = Carbon::now()->toDateString();

            $ventas = Venta::where('empresa_id', $empresaId)->get();
            $ventasHoy = $ventas->where('fecha_emision', $hoy);

            $totalUsd = (float) $ventas->where('estado', '!=', 'anulada')->sum('total_usd');
            $totalBs = (float) $ventas->where('estado', '!=', 'anulada')->sum('total_bs');

            $totalHoyUsd = (float) $ventasHoy->where('estado', '!=', 'anulada')->sum('total_usd');
            $totalHoyBs = (float) $ventasHoy->where('estado', '!=', 'anulada')->sum('total_bs');

            $totalCreditoUsd = (float) $ventas->where('condicion_pago', 'credito')->sum('saldo_pendiente_usd');
            $totalCreditoBs = (float) $ventas->where('condicion_pago', 'credito')->sum('saldo_pendiente_bs');

            $conteoTotal = $ventas->count();
            $conteoHoy = $ventasHoy->count();
            $conteoCredito = $ventas->where('condicion_pago', 'credito')->where('saldo_pendiente_usd', '>', 0)->count();
            $conteoDevueltas = $ventas->whereIn('estado', ['devuelta_parcial', 'devuelta_total', 'anulada'])->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_usd' => round($totalUsd, 2),
                    'total_bs' => round($totalBs, 2),
                    'total_hoy_usd' => round($totalHoyUsd, 2),
                    'total_hoy_bs' => round($totalHoyBs, 2),
                    'total_credito_usd' => round($totalCreditoUsd, 2),
                    'total_credito_bs' => round($totalCreditoBs, 2),
                    'conteo_total' => $conteoTotal,
                    'conteo_hoy' => $conteoHoy,
                    'conteo_credito' => $conteoCredito,
                    'conteo_devueltas' => $conteoDevueltas,
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

        $query = Venta::with(['cliente', 'almacen', 'usuario', 'detalles'])
            ->where('empresa_id', $empresaId)
            ->orderBy('id', 'desc');

        if ($fechaInicio = $request->input('fecha_inicio')) {
            $query->where('fecha_emision', '>=', $fechaInicio);
        }
        if ($fechaFin = $request->input('fecha_fin')) {
            $query->where('fecha_emision', '<=', $fechaFin);
        }
        if ($condicion = $request->input('condicion_pago')) {
            $query->where('condicion_pago', $condicion);
        }
        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }

        return datatables()->of($query)
            ->filter(function ($q) {
                if ($search = request('search.value')) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('codigo', 'LIKE', "%{$search}%")
                            ->orWhere('numero_control', 'LIKE', "%{$search}%")
                            ->orWhereHas('cliente', function ($cq) use ($search) {
                                $cq->where('nombre', 'LIKE', "%{$search}%")
                                    ->orWhere('apellido', 'LIKE', "%{$search}%")
                                    ->orWhere('cedula', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('almacen', function ($aq) use ($search) {
                                $aq->where('nombre', 'LIKE', "%{$search}%");
                            });
                    });
                }
            })
            ->toJson();
    }

    public function detalle(int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $venta = Venta::with([
                'cliente',
                'almacen',
                'usuario',
                'detalles.producto',
                'detalles.moto',
                'detalles.servicio',
                'detalles.almacen',
                'pagos.metodoPago',
                'cuentaPorCobrar.abonos.metodoPago',
                'devoluciones.detalles',
            ])
                ->where('empresa_id', $empresaId)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $venta,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Factura no encontrada: '.$e->getMessage(),
            ], 404);
        }
    }
}
