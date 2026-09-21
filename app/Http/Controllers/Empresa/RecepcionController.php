<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recepcion\CrearRequest;
use App\Models\Empresa;
use App\Service\Inventario\RecepcionClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RecepcionController extends Controller
{
    public function __construct(
        private RecepcionClass $recepcionService
    ) {}

    public function index(): View|RedirectResponse
    {
        $empresa = Auth::user()?->empresaActiva();
        if ($empresa && $empresa->maneja_motos) {
            return redirect()->route('recepcion_moto');
        }

        return view('Sistema.pages.empresa.recepcion');
    }

    public function catalogos(): JsonResponse
    {
        try {
            $data = $this->recepcionService->catalogos();

            return response()->json([
                'success' => true,
                'data' => $data,
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
        $recepciones = $this->recepcionService->lista();

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
            $recepcion = $this->recepcionService->guardar($request->validated());

            return response()->json([
                'success' => true,
                'message' => "Recepción {$recepcion->codigo} procesada exitosamente e inventario actualizado.",
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
            $recepcion = $this->recepcionService->detalles((int) $id);

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
        $recepcion = $this->recepcionService->detalles((int) $id);
        $empresa = Auth::user()?->empresaActiva() ?? Empresa::first();

        return view('Sistema.pages.empresa.recepcion-imprimir', compact('recepcion', 'empresa'));
    }

    public function anular(Request $request, $id): JsonResponse
    {
        try {
            $motivo = $request->input('motivo', 'Anulación administrativa');
            $recepcion = $this->recepcionService->anular((int) $id, $motivo);

            return response()->json([
                'success' => true,
                'message' => "La recepción {$recepcion->codigo} ha sido anulada y el stock fue revertido.",
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
