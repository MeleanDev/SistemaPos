<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\MetodoPago\ActualizarRequest;
use App\Http\Requests\MetodoPago\CrearRequest;
use App\Service\Empresa\MetodoPagoClass;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class MetodoPagoController extends Controller
{
    public function __construct(private MetodoPagoClass $metodoPagoClass) {}

    public function index(): View
    {
        return view('Sistema.pages.empresa.metodo_pago');
    }

    public function lista(): JsonResponse
    {
        $metodos = $this->metodoPagoClass->lista();

        return datatables()->of($metodos)
            ->filter(function ($query) {
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nombre', 'LIKE', "%{$search}%")
                            ->orWhere('descripcion', 'LIKE', "%{$search}%");
                    });
                }
            })
            ->toJson();
    }

    public function detalle($id): JsonResponse
    {
        try {
            $metodo = $this->metodoPagoClass->detalle($id);

            return response()->json($metodo);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Método de pago no encontrado',
            ], 404);
        }
    }

    public function guardar(CrearRequest $datos): JsonResponse
    {
        try {
            $metodo = $this->metodoPagoClass->guardar($datos->validated());

            return response()->json([
                'success' => true,
                'message' => 'Método de pago registrado correctamente',
                'data' => $metodo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function actualizar(ActualizarRequest $datos, $id): JsonResponse
    {
        try {
            $metodo = $this->metodoPagoClass->actualizar($datos->validated(), $id);

            return response()->json([
                'success' => true,
                'message' => 'Método de pago actualizado correctamente',
                'data' => $metodo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function eliminar($id): JsonResponse
    {
        try {
            $metodo = $this->metodoPagoClass->eliminar($id);

            return response()->json([
                'success' => true,
                'message' => 'Método de pago eliminado correctamente',
                'data' => $metodo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
