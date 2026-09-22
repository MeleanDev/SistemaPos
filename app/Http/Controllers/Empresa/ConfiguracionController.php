<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\ActualizarEmpresaRequest;
use App\Http\Requests\Configuracion\ActualizarTasasRequest;
use App\Service\Empresa\ConfiguracionEmpresaClass;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    public function __construct(private ConfiguracionEmpresaClass $configuracionClass) {}

    public function index(): View
    {
        return view('Sistema.pages.empresa.configuracion');
    }

    public function datos(): JsonResponse
    {
        try {
            $config = $this->configuracionClass->obtenerConfiguracion($this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'data' => $config,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar la configuración: '.$e->getMessage(),
            ], 500);
        }
    }

    public function actualizarEmpresa(ActualizarEmpresaRequest $datos): JsonResponse
    {
        try {
            $empresa = $this->configuracionClass->actualizarDatosEmpresa(
                $datos->validated(),
                $this->obtenerEmpresaId(),
                $datos->file('logo')
            );

            return response()->json([
                'success' => true,
                'message' => 'Datos de la empresa actualizados exitosamente.',
                'data' => $empresa,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar empresa: '.$e->getMessage(),
            ], 500);
        }
    }

    public function actualizarMonedas(ActualizarTasasRequest $datos): JsonResponse
    {
        try {
            $monedas = $this->configuracionClass->actualizarTasasMonedas(
                $datos->validated()['monedas'],
                $this->obtenerEmpresaId()
            );

            return response()->json([
                'success' => true,
                'message' => 'Tasas de cambio y monedas actualizadas exitosamente.',
                'data' => $monedas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar tasas de cambio: '.$e->getMessage(),
            ], 500);
        }
    }
}
