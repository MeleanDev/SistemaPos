<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventario\ModeloMoto\ActualizarRequest;
use App\Http\Requests\Inventario\ModeloMoto\CrearRequest;
use App\Service\Inventario\ModeloMotoClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class ModeloMotoController extends Controller
{
    public function __construct(
        private ModeloMotoClass $modeloMotoClass
    ) {}

    /**
     * DataTables query de modelos de moto
     */
    public function lista(): JsonResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $query = $this->modeloMotoClass->lista($empresaId);

        return DataTables::eloquent($query)
            ->addColumn('nombre_completo', function ($row) {
                return "{$row->marca} {$row->modelo}";
            })
            ->addColumn('especificaciones', function ($row) {
                return "{$row->anio} | {$row->color} | {$row->cilindrada}";
            })
            ->addColumn('acciones', function ($row) {
                return '';
            })
            ->rawColumns(['acciones'])
            ->make(true);
    }

    /**
     * Catálogo de modelos activos para selectores
     */
    public function catalogos(): JsonResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $modelos = $this->modeloMotoClass->catalogos($empresaId);
        $proximaReferencia = $this->modeloMotoClass->generarProximaReferencia($empresaId);

        return response()->json([
            'success' => true,
            'data' => [
                'modelos' => $modelos,
                'proxima_referencia' => $proximaReferencia,
            ],
        ]);
    }

    /**
     * Próxima referencia disponible
     */
    public function proximaReferencia(): JsonResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $proxima = $this->modeloMotoClass->generarProximaReferencia($empresaId);

        return response()->json([
            'success' => true,
            'data' => [
                'referencia' => $proxima,
            ],
        ]);
    }

    /**
     * Guardar nuevo modelo de moto
     */
    public function guardar(CrearRequest $request): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $modelo = $this->modeloMotoClass->guardar($request->validated(), $empresaId);

            return response()->json([
                'success' => true,
                'message' => "Modelo de moto #{$modelo->referencia} registrado exitosamente.",
                'data' => $modelo,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el modelo de moto: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener detalle de un modelo de moto
     */
    public function detalle(int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $modelo = $this->modeloMotoClass->detalle($id, $empresaId);

            return response()->json([
                'success' => true,
                'data' => $modelo,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Modelo de moto no encontrado.',
            ], 404);
        }
    }

    /**
     * Actualizar modelo de moto
     */
    public function actualizar(ActualizarRequest $request, int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $modelo = $this->modeloMotoClass->actualizar($request->validated(), $id, $empresaId);

            return response()->json([
                'success' => true,
                'message' => 'Modelo de moto actualizado exitosamente.',
                'data' => $modelo,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el modelo de moto: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Desactivar modelo de moto
     */
    public function eliminar(int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $this->modeloMotoClass->eliminar($id, $empresaId);

            return response()->json([
                'success' => true,
                'message' => 'Modelo de moto desactivado correctamente.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar el modelo de moto: '.$e->getMessage(),
            ], 500);
        }
    }
}
