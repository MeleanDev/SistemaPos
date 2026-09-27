<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecepcionMoto\CrearRequest;
use App\Http\Requests\RecepcionMoto\GuardarBorradorRequest;
use App\Models\Empresa;
use App\Service\Inventario\RecepcionMotoClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
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
            $empresaId = $this->obtenerEmpresaId();
            $catalogos = $this->recepcionMotoService->catalogos($empresaId);

            return response()->json([
                'success' => true,
                'data' => $catalogos,
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
        $empresaId = $this->obtenerEmpresaId();
        $recepciones = $this->recepcionMotoService->lista($empresaId);

        return datatables()->of($recepciones)
            ->filter(function ($query) {
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('recepcion_motos.codigo', 'LIKE', "%{$search}%")
                            ->orWhere('recepcion_motos.numero_documento', 'LIKE', "%{$search}%")
                            ->orWhereHas('proveedor', function ($pq) use ($search) {
                                $pq->where('proveedores.nombre', 'LIKE', "%{$search}%")
                                    ->orWhere('proveedores.rif', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('almacen', function ($aq) use ($search) {
                                $aq->where('almacenes.nombre', 'LIKE', "%{$search}%");
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
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
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

    public function guardarBorrador(GuardarBorradorRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $empresaId = $user->empresaActiva()->id;

            $borrador = $this->recepcionMotoService->guardarBorrador($request->validated(), $user->id, $empresaId);

            return response()->json([
                'success' => true,
                'message' => 'Borrador guardado exitosamente en el servidor.',
                'data' => $borrador,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar borrador: '.$e->getMessage(),
            ], 422);
        }
    }

    public function listarBorradores(): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $borradores = $this->recepcionMotoService->listarBorradores($empresaId);

            return response()->json([
                'success' => true,
                'data' => $borradores,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar borradores: '.$e->getMessage(),
            ], 500);
        }
    }

    public function recuperarBorrador(int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $borrador = $this->recepcionMotoService->recuperarBorrador($id, $empresaId);

            return response()->json([
                'success' => true,
                'data' => $borrador,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al recuperar borrador: '.$e->getMessage(),
            ], 404);
        }
    }

    public function eliminarBorrador(int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $this->recepcionMotoService->eliminarBorrador($id, $empresaId);

            return response()->json([
                'success' => true,
                'message' => 'Borrador eliminado correctamente.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar borrador: '.$e->getMessage(),
            ], 422);
        }
    }
}
