<?php

namespace App\Http\Controllers\Administradores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Empresa\ActualizarRequest;
use App\Http\Requests\Empresa\CrearRequest;
use App\Service\Administradores\EmpresaClass;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class EmpresaController extends Controller
{
    public function __construct(private EmpresaClass $empresaClass) {}

    public function index(): View
    {
        return view('Sistema.pages.administradores.empresa');
    }

    public function lista(): JsonResponse
    {
        $empresas = $this->empresaClass->lista()->get();

        return response()->json([
            'success' => true,
            'data' => $empresas,
        ]);
    }

    public function detalle($id): JsonResponse
    {
        try {
            $empresa = $this->empresaClass->detalle($id);

            return response()->json($empresa);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Empresa no encontrada',
            ], 404);
        }
    }

    public function guardar(CrearRequest $datos): JsonResponse
    {
        try {
            $empresa = $this->empresaClass->guardar($datos->validated());

            return response()->json([
                'success' => true,
                'message' => 'Empresa registrada correctamente',
                'data' => $empresa,
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
            $empresa = $this->empresaClass->actualizar($datos->validated(), $id);

            return response()->json([
                'success' => true,
                'message' => 'Empresa actualizada correctamente',
                'data' => $empresa,
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
            $empresa = $this->empresaClass->eliminar($id);

            return response()->json([
                'success' => true,
                'message' => 'Empresa eliminada correctamente',
                'data' => $empresa,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
