<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecepcionMoto\CrearRequest;
use App\Models\Almacen;
use App\Models\Empresa;
use App\Models\Proveedor;
use App\Service\Inventario\RecepcionMotoClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RecepcionMotoController extends Controller
{
    public function __construct(
        private RecepcionMotoClass $recepcionMotoService
    ) {}

    public function index(): View
    {
        return view('Sistema.pages.empresa.recepcion-moto');
    }

    public function catalogos(): JsonResponse
    {
        try {
            $empresaId = Auth::user()->empresaActiva()->id;

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

            $tasaOficial = $this->recepcionMotoService->obtenerTasaOficial($empresaId);
            $codigoSugerido = $this->recepcionMotoService->generarCodigo($empresaId);
            $proximaReferencia = $this->recepcionMotoService->generarReferenciaNumerica($empresaId);

            return response()->json([
                'success' => true,
                'data' => [
                    'proveedores' => $proveedores,
                    'almacenes' => $almacenes,
                    'tasa_oficial' => $tasaOficial,
                    'codigo_sugerido' => $codigoSugerido,
                    'proxima_referencia' => $proximaReferencia,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar catálogos: '.$e->getMessage(),
            ], 500);
        }
    }

    public function lista(): JsonResponse
    {
        $empresaId = Auth::user()->empresaActiva()->id;
        $recepciones = $this->recepcionMotoService->lista($empresaId);

        return datatables()->of($recepciones)
            ->filter(function ($query) {
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('codigo', 'LIKE', "%{$search}%")
                            ->orWhere('numero_documento', 'LIKE', "%{$search}%")
                            ->orWhereHas('proveedor', function ($pq) use ($search) {
                                $pq->where('nombre', 'LIKE', "%{$search}%")
                                    ->orWhere('rif', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('almacen', function ($aq) use ($search) {
                                $aq->where('nombre', 'LIKE', "%{$search}%");
                            });
                    });
                }
            })
            ->toJson();
    }

    public function guardar(CrearRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $empresaId = $user->empresaActiva()->id;

            $recepcion = $this->recepcionMotoService->guardar($request->validated(), $user, $empresaId);

            return response()->json([
                'success' => true,
                'message' => "Recepción de motos {$recepcion->codigo} procesada exitosamente con {$recepcion->total_unidades} unidades y seriales registrados.",
                'data' => $recepcion,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function detalle($id): JsonResponse
    {
        try {
            $empresaId = Auth::user()->empresaActiva()->id;
            $recepcion = $this->recepcionMotoService->detalle((int) $id, $empresaId);

            return response()->json([
                'success' => true,
                'data' => $recepcion,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Recepción no encontrada: '.$e->getMessage(),
            ], 404);
        }
    }

    public function imprimir($id): View
    {
        $empresa = Auth::user()?->empresaActiva() ?? Empresa::first();
        $recepcion = $this->recepcionMotoService->detalle((int) $id, $empresa->id);

        return view('Sistema.pages.empresa.recepcion-moto-imprimir', compact('recepcion', 'empresa'));
    }

    public function anular(Request $request, $id): JsonResponse
    {
        try {
            $empresaId = Auth::user()->empresaActiva()->id;
            $recepcion = $this->recepcionMotoService->anular((int) $id, $empresaId);

            return response()->json([
                'success' => true,
                'message' => "La recepción de motos {$recepcion->codigo} ha sido anulada exitosamente.",
                'data' => $recepcion,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
