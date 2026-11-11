<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\AnularFacturaRequest;
use App\Http\Requests\Pos\ProcesarDevolucionRequest;
use App\Models\CajaTurno;
use App\Models\Venta;
use App\Queries\VentaNetaQuery;
use App\Service\Ventas\VentaClass;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

            $totalesHistoricos = VentaNetaQuery::calcularTotales(VentaNetaQuery::validas($empresaId));
            $totalesHoy = VentaNetaQuery::calcularTotales(VentaNetaQuery::validas($empresaId)->whereDate('fecha_emision', $hoy));

            $totalUsd = $totalesHistoricos['total_neto_usd'];
            $totalBs = $totalesHistoricos['total_neto_bs'];

            $totalHoyUsd = $totalesHoy['total_neto_usd'];
            $totalHoyBs = $totalesHoy['total_neto_bs'];

            $ventasValidas = $ventas->where('estado', '!=', 'anulada');
            $totalCreditoUsd = (float) $ventasValidas->where('condicion_pago', 'credito')->sum('saldo_pendiente_usd');
            $totalCreditoBs = (float) $ventasValidas->where('condicion_pago', 'credito')->sum('saldo_pendiente_bs');

            $conteoTotal = $ventas->count();
            $conteoHoy = $ventasHoy->count();
            $conteoCredito = $ventasValidas->where('condicion_pago', 'credito')->where('saldo_pendiente_usd', '>', 0)->count();
            $conteoDevueltas = $ventas->whereIn('estado', ['devuelta_parcial', 'devuelta_total', 'anulada'])->count();

            $user = Auth::user();
            $turnoPropio = $user?->cajaTurnoActivo($empresaId);
            $turnosAbiertos = CajaTurno::with(['caja:id,nombre', 'usuario:id,name,nombre,apellido'])
                ->where('empresa_id', $empresaId)
                ->where('estado', 'abierta')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function (CajaTurno $turno) {
                    $cajeroNombre = trim(($turno->usuario?->nombre ?? '').' '.($turno->usuario?->apellido ?? ''));
                    if ($cajeroNombre === '') {
                        $cajeroNombre = $turno->usuario?->name ?? 'Cajero';
                    }

                    return [
                        'id' => $turno->id,
                        'caja_id' => $turno->caja_id,
                        'caja_nombre' => $turno->caja?->nombre ?? 'Caja #'.$turno->caja_id,
                        'cajero_nombre' => $cajeroNombre,
                    ];
                })
                ->values();

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
                    'turnos_abiertos' => $turnosAbiertos,
                    'caja_turno_propio_id' => $turnoPropio?->id,
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
        $user = Auth::user();
        $esAdminOCajero = $user && ($user->hasAnyRole(['SuperAdmin', 'Admin']) || $user->can('ventas.crear'));
        $vendedorPropio = $user?->vendedor()->where('empresa_id', $empresaId)->first();

        $query = Venta::with(['cliente', 'almacen', 'usuario', 'detalles', 'vendedor', 'caja'])
            ->where('empresa_id', $empresaId)
            ->orderBy('id', 'desc');

        if (! $esAdminOCajero && $vendedorPropio) {
            $query->where('vendedor_id', $vendedorPropio->id);
        }

        $hoy = Carbon::now()->toDateString();
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');

        if ($fechaInicio && $fechaInicio > $hoy) {
            $fechaInicio = $hoy;
        }
        if ($fechaFin && $fechaFin > $hoy) {
            $fechaFin = $hoy;
        }
        if ($fechaInicio && $fechaFin && $fechaInicio > $fechaFin) {
            $temp = $fechaInicio;
            $fechaInicio = $fechaFin;
            $fechaFin = $temp;
        }

        if ($fechaInicio) {
            $query->where('fecha_emision', '>=', $fechaInicio);
        }
        if ($fechaFin) {
            $query->where('fecha_emision', '<=', $fechaFin);
        }
        if ($condicion = $request->input('condicion_pago')) {
            $query->where('condicion_pago', $condicion);
        }
        if ($estado = $request->input('estado')) {
            $query->where('estado', $estado);
        }

        return datatables()->of($query)
            ->editColumn('fecha_emision', function (Venta $venta) {
                return $venta->fecha_emision ? Carbon::parse($venta->fecha_emision)->format('d/m/Y') : '--';
            })
            ->orderColumn('fecha_emision', 'fecha_emision $1')
            ->filter(function ($q) {
                if ($search = request('search.value')) {
                    $fechaSearch = null;
                    if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', trim($search), $m)) {
                        $fechaSearch = "{$m[3]}-{$m[2]}-{$m[1]}";
                    }

                    $q->where(function ($sub) use ($search, $fechaSearch) {
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

                        if ($fechaSearch) {
                            $sub->orWhere('fecha_emision', $fechaSearch);
                        } else {
                            $sub->orWhere('fecha_emision', 'LIKE', "%{$search}%");
                        }
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
                'vendedor',
                'caja',
                'cajaTurno',
                'detalles.producto',
                'detalles.moto',
                'detalles.servicio',
                'detalles.almacen',
                'pagos.metodoPago',
                'cuentaPorCobrar.abonos.metodoPago',
                'devoluciones.detalles',
                'anuladaPor',
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

    public function anular(AnularFacturaRequest $request, int $id): JsonResponse
    {
        try {
            $venta = $this->ventaService->anularVenta(
                $id,
                $request->validated(),
                $this->obtenerEmpresaId(),
                (int) Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => "Factura #{$venta->codigo} anulada exitosamente.",
                'data' => $venta,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al anular la factura: '.$e->getMessage(),
            ], 422);
        }
    }

    public function devolucionDatos(int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $venta = Venta::with([
                'cliente:id,nombre,apellido,cedula',
                'detalles.producto:id,nombre,codigo_interno',
                'detalles.moto:id,marca,modelo,placa',
                'detalles.servicio:id,nombre',
                'devoluciones.detalles',
            ])
                ->where('empresa_id', $empresaId)
                ->findOrFail($id);

            if (! in_array($venta->estado, ['completada', 'devuelta_parcial'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta factura se encuentra en estado "'.$venta->estado.'" y no permite devoluciones adicionales.',
                ], 422);
            }

            $detallesConDev = $venta->detalles->map(function ($det) use ($venta) {
                $cantDevueltaPrevia = 0;
                foreach ($venta->devoluciones as $dev) {
                    foreach ($dev->detalles as $dd) {
                        if ($dd->venta_detalle_id === $det->id) {
                            $cantDevueltaPrevia += (float) ($dd->cantidad ?? 0);
                        }
                    }
                }

                $cantDisponible = max(0, (float) $det->cantidad - $cantDevueltaPrevia);
                $nombre = $det->nombre_item;
                if (! $nombre && $det->producto) {
                    $nombre = $det->producto->nombre;
                }
                if (! $nombre && $det->moto) {
                    $placa = $det->moto->placa ? '['.$det->moto->placa.']' : '';
                    $nombre = trim($det->moto->marca.' '.$det->moto->modelo.' '.$placa);
                }
                if (! $nombre && $det->servicio) {
                    $nombre = $det->servicio->nombre;
                }
                if (! $nombre) {
                    $nombre = 'Ítem';
                }

                return [
                    'id' => $det->id,
                    'nombre' => $nombre,
                    'tipo_item' => $det->tipo_item,
                    'cantidad_facturada' => (float) $det->cantidad,
                    'cantidad_devuelta' => $cantDevueltaPrevia,
                    'cantidad_disponible' => $cantDisponible,
                    'precio_unitario_usd' => (float) $det->precio_unitario_usd,
                    'subtotal_usd' => (float) $det->subtotal_usd,
                ];
            })->filter(fn ($d) => $d['cantidad_disponible'] > 0)->values();

            if ($detallesConDev->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Todos los ítems de esta factura ya han sido devueltos.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $venta->id,
                    'codigo' => $venta->codigo,
                    'numero_control' => $venta->numero_control,
                    'fecha_emision' => $venta->fecha_emision,
                    'total_usd' => (float) $venta->total_usd,
                    'total_bs' => (float) $venta->total_bs,
                    'cliente_nombre' => $venta->cliente ? trim($venta->cliente->nombre.' '.($venta->cliente->apellido ?? '')) : 'Consumidor Final',
                    'cliente_cedula' => $venta->cliente?->cedula ?? 'V-00000000',
                    'detalles' => $detallesConDev,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos de devolución: '.$e->getMessage(),
            ], 422);
        }
    }

    public function devolver(ProcesarDevolucionRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['venta_id'] = $id;

            $devolucion = $this->ventaService->procesarDevolucion(
                $data,
                $this->obtenerEmpresaId(),
                (int) Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => "Devolución #{$devolucion->codigo} procesada y stock reintegrado.",
                'data' => $devolucion,
            ]);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar devolución: '.$e->getMessage(),
            ], 422);
        }
    }
}
