<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendedor\ActualizarRequest;
use App\Http\Requests\Vendedor\CrearRequest;
use App\Http\Requests\Vendedor\LiquidarComisionRequest;
use App\Models\Empresa;
use App\Service\Empresa\VendedorClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VendedorController extends Controller
{
    public function __construct(private VendedorClass $vendedorClass) {}

    private function verificarModuloVendedoresHabilitado(): int
    {
        $empresa = $this->obtenerEmpresaActiva() ?? Empresa::find($this->obtenerEmpresaId());
        if (! $empresa || ! $empresa->maneja_vendedores) {
            abort(403, 'El módulo de vendedores no está habilitado para esta empresa.');
        }

        return (int) $empresa->id;
    }

    public function index(): View
    {
        $this->verificarModuloVendedoresHabilitado();

        return view('Sistema.pages.empresa.vendedor');
    }

    public function catalogos(): JsonResponse
    {
        $empresaId = $this->verificarModuloVendedoresHabilitado();
        $usuarios = $this->vendedorClass->usuariosDisponibles($empresaId);

        return response()->json([
            'success' => true,
            'usuarios' => $usuarios,
        ]);
    }

    public function lista(): JsonResponse
    {
        $empresaId = $this->verificarModuloVendedoresHabilitado();
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
        $empresaId = $this->verificarModuloVendedoresHabilitado();

        try {
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
        $empresaId = $this->verificarModuloVendedoresHabilitado();
        $vendedores = $this->vendedorClass->activos($empresaId);

        return response()->json([
            'success' => true,
            'data' => $vendedores,
        ]);
    }

    public function guardar(CrearRequest $request): JsonResponse
    {
        $empresaId = $this->verificarModuloVendedoresHabilitado();

        try {
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
        $empresaId = $this->verificarModuloVendedoresHabilitado();

        try {
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
        $empresaId = $this->verificarModuloVendedoresHabilitado();

        try {
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

    public function comisionesPendientes(Request $request, int $id): JsonResponse
    {
        $empresaId = $this->verificarModuloVendedoresHabilitado();

        try {
            $datos = $this->vendedorClass->comisionesPendientes(
                $id,
                $empresaId,
                $request->input('fecha_inicio'),
                $request->input('fecha_fin')
            );

            return response()->json([
                'success' => true,
                'data' => $datos,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function liquidarComisiones(LiquidarComisionRequest $request, int $id): JsonResponse
    {
        $empresaId = $this->verificarModuloVendedoresHabilitado();

        try {
            $liquidacion = $this->vendedorClass->liquidarComisiones(
                $id,
                $request->validated(),
                $empresaId,
                (int) Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => "Liquidación #{$liquidacion->codigo} registrada correctamente.",
                'data' => $liquidacion,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
