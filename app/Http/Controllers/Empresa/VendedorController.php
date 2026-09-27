<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\ActualizarRequest;
use App\Http\Requests\Vendedor\CrearRequest;
use App\Models\User;
use App\Service\Empresa\VendedorClass;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class VendedorController extends Controller
{
    public function __construct(private VendedorClass $vendedorClass) {}

    public function index(): View
    {
        $empresaId = $this->obtenerEmpresaId();
        $usuarios = User::where('estado', true)
            ->where(function ($query) use ($empresaId) {
                $query->whereHas('empresas', fn ($q) => $q->where('empresas.id', $empresaId))
                    ->orWhereHas('roles', fn ($q) => $q->whereIn('name', ['SuperAdmin', 'Admin', 'Operador', 'Vendedor']));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'nombre', 'apellido', 'email']);

        return view('Sistema.pages.empresa.vendedor', compact('usuarios'));
    }

    public function lista(): JsonResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $vendedores = $this->vendedorClass->lista($empresaId);

        return datatables()->of($vendedores)
            ->filter(function ($query) {
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nombre', 'LIKE', "%{$search}%")
                            ->orWhere('documento', 'LIKE', "%{$search}%")
                            ->orWhere('telefono', 'LIKE', "%{$search}%")
                            ->orWhere('correo', 'LIKE', "%{$search}%");
                    });
                }
            })
            ->toJson();
    }

    public function detalle(int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $vendedor = $this->vendedorClass->detalle($id, $empresaId);

            return response()->json($vendedor);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vendedor no encontrado',
            ], 404);
        }
    }

    public function activos(): JsonResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $vendedores = $this->vendedorClass->activos($empresaId);

        return response()->json([
            'success' => true,
            'data' => $vendedores,
        ]);
    }

    public function guardar(CrearRequest $request): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $vendedor = $this->vendedorClass->guardar($request->validated(), $empresaId);

            return response()->json([
                'success' => true,
                'message' => 'Vendedor registrado correctamente',
                'data' => $vendedor,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function actualizar(ActualizarRequest $request, int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $vendedor = $this->vendedorClass->actualizar($request->validated(), $id, $empresaId);

            return response()->json([
                'success' => true,
                'message' => 'Vendedor actualizado correctamente',
                'data' => $vendedor,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function eliminar(int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $vendedor = $this->vendedorClass->eliminar($id, $empresaId);

            $mensaje = $vendedor->estado ? 'Vendedor reactivado correctamente' : 'Vendedor desactivado correctamente';

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => $vendedor,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
