<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Producto\ActualizarRequest;
use App\Http\Requests\Producto\CrearRequest;
use App\Service\Empresa\ProductoClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProductoController extends Controller
{
    public function __construct(private ProductoClass $productoClass) {}

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
        return view('Sistema.pages.empresa.producto');
    }

    public function catalogos(): JsonResponse
    {
        try {
            $catalogos = $this->productoClass->catalogos($this->obtenerEmpresaId());

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
        $productos = $this->productoClass->lista($this->obtenerEmpresaId());

        return datatables()->of($productos)
            ->filter(function ($query) {
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nombre', 'LIKE', "%{$search}%")
                            ->orWhere('codigo_interno', 'LIKE', "%{$search}%")
                            ->orWhere('descripcion', 'LIKE', "%{$search}%")
                            ->orWhereHas('categoria', function ($cq) use ($search) {
                                $cq->where('nombre', 'LIKE', "%{$search}%");
                            })
                            ->orWhereHas('codigosBarra', function ($bq) use ($search) {
                                $bq->where('codigo_barra', 'LIKE', "%{$search}%");
                            });
                    });
                }
            })
            ->toJson();
    }

    public function detalle($id): JsonResponse
    {
        try {
            $producto = $this->productoClass->detalle((int) $id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'data' => $producto,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Producto no encontrado: '.$e->getMessage(),
            ], 404);
        }
    }

    public function guardar(CrearRequest $datos): JsonResponse
    {
        try {
            $producto = $this->productoClass->guardar(
                $datos->validated(),
                $this->obtenerEmpresaId()
            );

            return response()->json([
                'success' => true,
                'message' => 'Producto registrado correctamente',
                'data' => $producto,
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
            $producto = $this->productoClass->actualizar(
                $datos->validated(),
                (int) $id,
                $this->obtenerEmpresaId()
            );

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado correctamente',
                'data' => $producto,
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
            $producto = $this->productoClass->eliminar((int) $id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => 'Estado del producto actualizado correctamente',
                'data' => $producto,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
