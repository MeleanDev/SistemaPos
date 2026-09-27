<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kardex\AjusteInventarioRequest;
use App\Http\Requests\Kardex\TrasladoInventarioRequest;
use App\Service\Inventario\KardexClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class KardexController extends Controller
{
    public function __construct(
        private KardexClass $kardexClass
    ) {}

    /**
     * Vista principal del Módulo de Kardex & Movimientos
     */
    public function index(): View
    {
        return view('Sistema.pages.empresa.kardex');
    }

    /**
     * Listado JSON de movimientos de Kardex para DataTables
     */
    public function lista(): JsonResponse
    {
        $query = $this->kardexClass->listaQuery($this->obtenerEmpresaId());

        return datatables()->of($query)
            ->filter(function ($query) {
                if ($almacenId = request('almacen_id')) {
                    $query->where('almacen_id', (int) $almacenId);
                }
                if ($tipoMovimiento = request('tipo_movimiento')) {
                    $query->where('tipo_movimiento', $tipoMovimiento);
                }
                if ($fechaDesde = request('fecha_desde')) {
                    $query->whereDate('created_at', '>=', $fechaDesde);
                }
                if ($fechaHasta = request('fecha_hasta')) {
                    $query->whereDate('created_at', '<=', $fechaHasta);
                }
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('motivo', 'like', "%{$search}%")
                            ->orWhere('documento_tipo', 'like', "%{$search}%")
                            ->orWhereHas('producto', function ($qp) use ($search) {
                                $qp->where('nombre', 'like', "%{$search}%")
                                    ->orWhere('codigo_interno', 'like', "%{$search}%");
                            })
                            ->orWhereHas('almacen', function ($qa) use ($search) {
                                $qa->where('nombre', 'like', "%{$search}%")
                                    ->orWhere('codigo', 'like', "%{$search}%");
                            });
                    });
                }
            })
            ->toJson();
    }

    /**
     * Obtener KPIs y estadísticas de inventario
     */
    public function kpis(): JsonResponse
    {
        $kpis = $this->kardexClass->obtenerKpis($this->obtenerEmpresaId());

        return response()->json([
            'success' => true,
            'data' => $kpis,
        ]);
    }

    /**
     * Catálogos para los modales de Ajustes y Traslados
     */
    public function catalogos(): JsonResponse
    {
        $catalogos = $this->kardexClass->obtenerCatalogos($this->obtenerEmpresaId());

        return response()->json([
            'success' => true,
            'data' => $catalogos,
        ]);
    }

    /**
     * Stock de un producto desglosado por almacén
     */
    public function stockProducto(int $productoId): JsonResponse
    {
        $stock = $this->kardexClass->obtenerStockProductoAlmacenes($productoId, $this->obtenerEmpresaId());

        return response()->json([
            'success' => true,
            'data' => $stock,
        ]);
    }

    /**
     * Procesar Ajuste de Inventario (Entrada o Salida)
     */
    public function ajustar(AjusteInventarioRequest $request): JsonResponse
    {
        $movimiento = $this->kardexClass->procesarAjuste(
            $request->validated(),
            $this->obtenerEmpresaId(),
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Ajuste de inventario procesado correctamente.',
            'data' => $movimiento,
        ]);
    }

    /**
     * Procesar Traslado de Stock entre Almacenes
     */
    public function trasladar(TrasladoInventarioRequest $request): JsonResponse
    {
        $movimiento = $this->kardexClass->procesarTraslado(
            $request->validated(),
            $this->obtenerEmpresaId(),
            Auth::id()
        );

        return response()->json([
            'success' => true,
            'message' => 'Traslado de mercancía completado con éxito.',
            'data' => $movimiento,
        ]);
    }

    /**
     * Comprobante oficial de Movimiento / Guía de Traslado
     */
    public function comprobante(int $id): View
    {
        $movimiento = $this->kardexClass->obtenerComprobante($id, $this->obtenerEmpresaId());

        return view('Sistema.pages.empresa.kardex-comprobante', compact('movimiento'));
    }
}
