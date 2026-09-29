<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Service\Inventario\ConsultorPrecioClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsultorPrecioController extends Controller
{
    public function __construct(
        protected ConsultorPrecioClass $consultorPrecioClass
    ) {}

    /**
     * Vista principal del consultor de precios
     */
    public function index(): View
    {
        $empresaId = $this->obtenerEmpresaId();
        $tasaUsd = $this->consultorPrecioClass->obtenerTasaActiva($empresaId);

        return view('Sistema.pages.empresa.consultor-precios', compact('tasaUsd'));
    }

    /**
     * Obtener datos iniciales (tasa de cambio activa)
     */
    public function datos(): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $datos = $this->consultorPrecioClass->datosIniciales($empresaId);

            return response()->json([
                'success' => true,
                'data' => $datos,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar datos del consultor: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Buscar productos y obtener precios con IVA
     */
    public function buscar(Request $request): JsonResponse
    {
        try {
            $termino = (string) $request->input('termino', '');
            $empresaId = $this->obtenerEmpresaId();
            $productos = $this->consultorPrecioClass->buscar($termino, $empresaId);

            return response()->json([
                'success' => true,
                'data' => $productos,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar productos: '.$e->getMessage(),
            ], 500);
        }
    }
}
