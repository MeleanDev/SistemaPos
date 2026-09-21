<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finanzas\CuentaPorPagar\AbonarFacturaRequest;
use App\Http\Requests\Finanzas\CuentaPorPagar\AbonarGeneralRequest;
use App\Service\Finanzas\CuentaPorPagarClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CuentaPorPagarController extends Controller
{
    public function __construct(
        private CuentaPorPagarClass $cuentaPorPagarClass
    ) {}

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
        return view('Sistema.pages.empresa.cxp');
    }

    public function lista(): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $resumen = $this->cuentaPorPagarClass->resumenProveedores($empresaId);

            return response()->json([
                'success' => true,
                'data' => $resumen,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar listado de cuentas por pagar: '.$e->getMessage(),
            ], 500);
        }
    }

    public function catalogos(): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $catalogos = $this->cuentaPorPagarClass->catalogos($empresaId);

            return response()->json([
                'success' => true,
                'data' => $catalogos,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar métodos de pago: '.$e->getMessage(),
            ], 500);
        }
    }

    public function proveedorDetalle(int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $detalle = $this->cuentaPorPagarClass->detalleProveedorFacturas($id, $empresaId);

            return response()->json([
                'success' => true,
                'data' => $detalle,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado de cuenta del proveedor: '.$e->getMessage(),
            ], 404);
        }
    }

    public function abonarFactura(AbonarFacturaRequest $request): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $userId = (int) Auth::id();

            $resultado = $this->cuentaPorPagarClass->abonarFacturaEspecifica(
                (int) $request->validated('cuenta_id'),
                $request->validated(),
                $userId,
                $empresaId
            );

            return response()->json([
                'success' => true,
                'message' => $resultado['mensaje'],
                'data' => $resultado,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function abonarGeneral(AbonarGeneralRequest $request): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $userId = (int) Auth::id();

            $resultado = $this->cuentaPorPagarClass->abonarGeneralDeuda(
                (int) $request->validated('proveedor_id'),
                $request->validated(),
                $userId,
                $empresaId
            );

            return response()->json([
                'success' => true,
                'message' => $resultado['mensaje'],
                'data' => $resultado,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function imprimirTicket(int $id): View
    {
        $empresaId = $this->obtenerEmpresaId();
        $datos = $this->cuentaPorPagarClass->obtenerComprobanteAbono($id, $empresaId);

        return view('Sistema.pages.empresa.ticket-abono-cxp', $datos);
    }
}
