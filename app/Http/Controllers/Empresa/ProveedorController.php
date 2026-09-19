<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Proveedor\ActualizarRequest;
use App\Http\Requests\Proveedor\CrearRequest;
use App\Service\Empresa\ProveedorClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProveedorController extends Controller
{
    public function __construct(private ProveedorClass $proveedorClass) {}

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
        return view('Sistema.pages.empresa.proveedor');
    }

    public function lista(): JsonResponse
    {
        $proveedores = $this->proveedorClass->lista($this->obtenerEmpresaId());

        return datatables()->of($proveedores)
            ->filter(function ($query) {
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nombre', 'LIKE', "%{$search}%")
                            ->orWhere('razon_social', 'LIKE', "%{$search}%")
                            ->orWhere('rif', 'LIKE', "%{$search}%")
                            ->orWhere('nombre_contacto', 'LIKE', "%{$search}%")
                            ->orWhere('telefono', 'LIKE', "%{$search}%")
                            ->orWhere('correo', 'LIKE', "%{$search}%");
                    });
                }
            })
            ->toJson();
    }

    public function detalle($id): JsonResponse
    {
        try {
            $proveedor = $this->proveedorClass->detalle($id, $this->obtenerEmpresaId());

            return response()->json($proveedor);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Proveedor no encontrado',
            ], 404);
        }
    }

    public function guardar(CrearRequest $datos): JsonResponse
    {
        try {
            $proveedor = $this->proveedorClass->guardar($datos->validated(), $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => 'Proveedor registrado correctamente',
                'data' => $proveedor,
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
            $proveedor = $this->proveedorClass->actualizar($datos->validated(), $id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado correctamente',
                'data' => $proveedor,
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
            $proveedor = $this->proveedorClass->eliminar($id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => 'Proveedor eliminado correctamente',
                'data' => $proveedor,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
