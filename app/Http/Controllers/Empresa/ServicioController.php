<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Servicio\ActualizarRequest;
use App\Http\Requests\Servicio\CrearRequest;
use App\Service\Empresa\ServicioClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ServicioController extends Controller
{
    public function __construct(private ServicioClass $servicioClass) {}

    private function obtenerEmpresaId(): int
    {
        $empresa = Auth::user()?->empresaActiva();

        if (! $empresa) {
            abort(403, 'No tienes una empresa activa asignada.');
        }

        return $empresa->id;
    }

    public function index(): View
    {
        return view('Sistema.pages.empresa.servicio');
    }

    public function catalogos(): JsonResponse
    {
        try {
            $catalogos = $this->servicioClass->catalogos($this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'data' => $catalogos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar catálogos: '.$e->getMessage(),
            ], 500);
        }
    }

    public function lista(): JsonResponse
    {
        $servicios = $this->servicioClass->lista($this->obtenerEmpresaId());

        return datatables()->of($servicios)
            ->filter(function ($query) {
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nombre', 'LIKE', "%{$search}%")
                            ->orWhere('codigo', 'LIKE', "%{$search}%")
                            ->orWhere('descripcion', 'LIKE', "%{$search}%")
                            ->orWhereHas('categoria', function ($cq) use ($search) {
                                $cq->where('nombre', 'LIKE', "%{$search}%");
                            });
                    });
                }
            })
            ->toJson();
    }

    public function detalle($id): JsonResponse
    {
        try {
            $servicio = $this->servicioClass->detalle((int) $id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'data' => $servicio,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Servicio no encontrado: '.$e->getMessage(),
            ], 404);
        }
    }

    public function guardar(CrearRequest $datos): JsonResponse
    {
        try {
            $servicio = $this->servicioClass->guardar(
                $datos->validated(),
                $this->obtenerEmpresaId()
            );

            return response()->json([
                'success' => true,
                'message' => 'Servicio registrado correctamente.',
                'data' => $servicio,
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
            $servicio = $this->servicioClass->actualizar(
                $datos->validated(),
                (int) $id,
                $this->obtenerEmpresaId()
            );

            return response()->json([
                'success' => true,
                'message' => 'Servicio actualizado correctamente.',
                'data' => $servicio,
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
            $servicio = $this->servicioClass->eliminar((int) $id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => 'Estado del servicio actualizado correctamente.',
                'data' => $servicio,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
