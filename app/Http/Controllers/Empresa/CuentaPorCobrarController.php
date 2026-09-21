<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Service\Finanzas\CuentaPorCobrarClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CuentaPorCobrarController extends Controller
{
    public function __construct(
        protected CuentaPorCobrarClass $cxcService
    ) {}

    protected function empresaId(): int
    {
        $user = Auth::user();
        $empresaId = session('empresa_activa_id');

        if (! $empresaId && $user) {
            $empresa = $user->empresaActiva();
            $empresaId = $empresa?->id;
        }

        if (! $empresaId) {
            throw new Exception('No hay una empresa activa seleccionada en la sesión.');
        }

        return (int) $empresaId;
    }

    /**
     * Vista principal de Cuentas por Cobrar
     */
    public function index(): View
    {
        return view('Sistema.pages.empresa.cxc');
    }

    /**
     * Resumen de clientes con deudas y KPIs
     */
    public function lista(): JsonResponse
    {
        try {
            $empresaId = $this->empresaId();
            $resumen = $this->cxcService->resumenClientes($empresaId);

            return response()->json([
                'success' => true,
                'data' => $resumen,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar listado de cuentas por cobrar: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Catálogos para el modal de abono (métodos de pago y tasa de cambio)
     */
    public function catalogos(): JsonResponse
    {
        try {
            $empresaId = $this->empresaId();
            $catalogos = $this->cxcService->catalogos($empresaId);

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

    /**
     * Detalle completo de facturas e historial de un cliente
     */
    public function clienteDetalle(int $id): JsonResponse
    {
        try {
            $empresaId = $this->empresaId();
            $detalle = $this->cxcService->detalleClienteFacturas($id, $empresaId);

            return response()->json([
                'success' => true,
                'data' => $detalle,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado de cuenta del cliente: '.$e->getMessage(),
            ], 404);
        }
    }

    /**
     * Procesar abono a factura específica
     */
    public function abonarFactura(Request $request): JsonResponse
    {
        $request->validate([
            'cuenta_id' => ['required', 'integer'],
            'metodo_pago_id' => ['required', 'integer', 'exists:metodos_pago,id'],
            'moneda' => ['required', 'string', 'in:USD,VES'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'tasa_cambio' => ['required', 'numeric', 'min:0.0001'],
            'fecha_abono' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $empresaId = $this->empresaId();
            $userId = (int) Auth::id();

            $resultado = $this->cxcService->abonarFacturaEspecifica(
                (int) $request->input('cuenta_id'),
                $request->all(),
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

    /**
     * Procesar abono general FIFO a la deuda total del cliente
     */
    public function abonarGeneral(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'metodo_pago_id' => ['required', 'integer', 'exists:metodos_pago,id'],
            'moneda' => ['required', 'string', 'in:USD,VES'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'tasa_cambio' => ['required', 'numeric', 'min:0.0001'],
            'fecha_abono' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $empresaId = $this->empresaId();
            $userId = (int) Auth::id();

            $resultado = $this->cxcService->abonarGeneralDeuda(
                (int) $request->input('cliente_id'),
                $request->all(),
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

    /**
     * Vista de impresión térmica del comprobante de abono
     */
    public function imprimirTicket(int $id): View
    {
        $empresaId = $this->empresaId();
        $datos = $this->cxcService->obtenerComprobanteAbono($id, $empresaId);

        return view('Sistema.pages.empresa.ticket-abono-cxc', $datos);
    }
}
