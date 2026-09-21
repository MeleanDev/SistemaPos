<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Almacen\ActualizarRequest;
use App\Http\Requests\Almacen\CrearRequest;
use App\Service\Empresa\AlmacenClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AlmacenController extends Controller
{
    public function __construct(private AlmacenClass $almacenClass) {}

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
        return view('Sistema.pages.empresa.almacen');
    }

    public function lista(): JsonResponse
    {
        try {
            $almacenes = $this->almacenClass->lista($this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'data' => $almacenes,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la lista de almacenes: '.$e->getMessage(),
            ], 500);
        }
    }

    public function detalle(int $id): JsonResponse
    {
        try {
            $almacen = $this->almacenClass->detalle($id, $this->obtenerEmpresaId());

            return response()->json($almacen);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Almacén no encontrado',
            ], 404);
        }
    }

    public function guardar(CrearRequest $request): JsonResponse
    {
        try {
            $almacen = $this->almacenClass->guardar($request->validated(), $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => 'Almacén registrado correctamente',
                'data' => $almacen,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function actualizar(ActualizarRequest $request, int $id): JsonResponse
    {
        try {
            $almacen = $this->almacenClass->actualizar($request->validated(), $id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => 'Almacén actualizado correctamente',
                'data' => $almacen,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function eliminar(int $id): JsonResponse
    {
        try {
            $almacen = $this->almacenClass->eliminar($id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => 'Almacén eliminado correctamente',
                'data' => $almacen,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
