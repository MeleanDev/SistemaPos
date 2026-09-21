<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Categoria\ActualizarRequest;
use App\Http\Requests\Categoria\CrearRequest;
use App\Service\Empresa\CategoriaClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CategoriaController extends Controller
{
    public function __construct(private CategoriaClass $categoriaClass) {}

    private function obtenerEmpresaId(): int
    {
        $empresa = Auth::user()?->empresaActiva();

        if (! $empresa) {
            abort(403, 'No tienes una empresa activa asignada.');
        }

        return $empresa->id;
    }

    public function index(): View|RedirectResponse
    {
        $empresa = Auth::user()?->empresaActiva();
        if ($empresa && $empresa->maneja_motos) {
            return redirect()->route('moto');
        }

        return view('Sistema.pages.empresa.categoria');
    }

    public function lista(): JsonResponse
    {
        $categorias = $this->categoriaClass->lista($this->obtenerEmpresaId());

        return datatables()->of($categorias)
            ->filter(function ($query) {
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nombre', 'LIKE', "%{$search}%")
                            ->orWhere('codigo', 'LIKE', "%{$search}%")
                            ->orWhere('descripcion', 'LIKE', "%{$search}%");
                    });
                }
            })
            ->toJson();
    }

    public function detalle($id): JsonResponse
    {
        try {
            $categoria = $this->categoriaClass->detalle((int) $id, $this->obtenerEmpresaId());

            return response()->json($categoria);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada',
            ], 404);
        }
    }

    public function guardar(CrearRequest $datos): JsonResponse
    {
        try {
            $categoria = $this->categoriaClass->guardar($datos->validated(), $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => 'Categoría registrada correctamente',
                'data' => $categoria,
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
            $categoria = $this->categoriaClass->actualizar($datos->validated(), (int) $id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada correctamente',
                'data' => $categoria,
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
            $categoria = $this->categoriaClass->eliminar((int) $id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => 'Estado de la categoría actualizado correctamente',
                'data' => $categoria,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
