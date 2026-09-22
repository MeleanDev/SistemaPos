<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Moto\ActualizarRequest;
use App\Service\Inventario\MotoClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MotoController extends Controller
{
    public function __construct(
        private MotoClass $motoService
    ) {}

    public function index(): View
    {
        return view('Sistema.pages.empresa.moto');
    }

    public function lista(): JsonResponse
    {
        $motos = $this->motoService->lista($this->obtenerEmpresaId());

        return datatables()->of($motos)
            ->filter(function ($query) {
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('marca', 'LIKE', "%{$search}%")
                            ->orWhere('modelo', 'LIKE', "%{$search}%")
                            ->orWhere('referencia', 'LIKE', "%{$search}%")
                            ->orWhere('numero_niv', 'LIKE', "%{$search}%")
                            ->orWhere('numero_chasis', 'LIKE', "%{$search}%")
                            ->orWhere('numero_motor', 'LIKE', "%{$search}%")
                            ->orWhere('certificado_origen', 'LIKE', "%{$search}%")
                            ->orWhere('color', 'LIKE', "%{$search}%")
                            ->orWhere('placa', 'LIKE', "%{$search}%")
                            ->orWhereHas('almacen', function ($aq) use ($search) {
                                $aq->where('nombre', 'LIKE', "%{$search}%");
                            });
                    });
                }
            })
            ->toJson();
    }

    public function detalle($id): JsonResponse
    {
        try {
            $moto = $this->motoService->detalle((int) $id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'data' => $moto,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Moto no encontrada: '.$e->getMessage(),
            ], 404);
        }
    }

    public function actualizar(ActualizarRequest $request, $id): JsonResponse
    {
        try {
            $moto = $this->motoService->actualizar($request->validated(), (int) $id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => "Moto {$moto->marca} {$moto->modelo} (NIV: {$moto->numero_niv}) actualizada exitosamente.",
                'data' => $moto,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cambiarEstado(Request $request, $id): JsonResponse
    {
        try {
            $estado = (string) $request->input('estado', 'disponible');
            $moto = $this->motoService->cambiarEstado((int) $id, $estado, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => "Estado de la moto actualizado a: {$estado}.",
                'data' => $moto,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
