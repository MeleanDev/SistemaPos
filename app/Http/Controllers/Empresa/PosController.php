<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\ClienteRapidoRequest;
use App\Http\Requests\Pos\GuardarEnEsperaRequest;
use App\Http\Requests\Pos\GuardarVentaRequest;
use App\Http\Requests\Pos\ProcesarDevolucionRequest;
use App\Service\Ventas\VentaClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        protected VentaClass $ventaService
    ) {}

    public function index(): View
    {
        return view('Sistema.pages.empresa.pos');
    }

    public function datos(): JsonResponse
    {
        try {
            $datos = $this->ventaService->datosInicialesPos($this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'data' => $datos,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar datos del POS: '.$e->getMessage(),
            ], 500);
        }
    }

    public function buscarClientes(Request $request): JsonResponse
    {
        $termino = (string) $request->input('termino', '');
        $clientes = $this->ventaService->buscarClientes($termino);

        return response()->json([
            'success' => true,
            'data' => $clientes,
        ]);
    }

    public function guardarClienteRapido(ClienteRapidoRequest $request): JsonResponse
    {
        try {
            $cliente = $this->ventaService->guardarClienteRapido($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Cliente guardado correctamente.',
                'data' => $cliente,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function guardar(GuardarVentaRequest $request): JsonResponse
    {
        try {
            $venta = $this->ventaService->procesarVenta(
                $request->validated(),
                $this->obtenerEmpresaId(),
                (int) Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => "Venta #{$venta->codigo} procesada exitosamente.",
                'data' => $venta,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la venta: '.$e->getMessage(),
            ], 422);
        }
    }

    public function guardarEnEspera(GuardarEnEsperaRequest $request): JsonResponse
    {
        try {
            $espera = $this->ventaService->guardarEnEspera(
                $request->validated(),
                $this->obtenerEmpresaId(),
                (int) Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Venta colocada en espera exitosamente.',
                'data' => $espera,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar en espera: '.$e->getMessage(),
            ], 422);
        }
    }

    public function listarEnEspera(): JsonResponse
    {
        try {
            $lista = $this->ventaService->listarEnEspera($this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'data' => $lista,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function recuperarEnEspera(int $id): JsonResponse
    {
        try {
            $datos = $this->ventaService->recuperarEnEspera($id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'data' => $datos,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function eliminarEnEspera(int $id): JsonResponse
    {
        try {
            $this->ventaService->eliminarEnEspera($id, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'message' => 'Cuenta en espera descartada.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function buscarFacturaDevolucion(Request $request): JsonResponse
    {
        $busqueda = (string) $request->input('busqueda', '');

        try {
            $venta = $this->ventaService->buscarFacturaDevolucion($busqueda, $this->obtenerEmpresaId());

            return response()->json([
                'success' => true,
                'data' => $venta,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function procesarDevolucion(ProcesarDevolucionRequest $request): JsonResponse
    {
        try {
            $devolucion = $this->ventaService->procesarDevolucion(
                $request->validated(),
                $this->obtenerEmpresaId(),
                (int) Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => "Devolución #{$devolucion->codigo} procesada y stock reintegrado.",
                'data' => $devolucion,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar devolución: '.$e->getMessage(),
            ], 422);
        }
    }

    public function imprimir(Request $request, string $id): View
    {
        $formato = $request->query('formato', 'carta');
        if ($formato === 'ticket') {
            return $this->imprimirTicket($id);
        }

        return $this->imprimirCarta($id);
    }

    public function imprimirCarta(string $id): View
    {
        $venta = $this->ventaService->obtenerVentaParaImpresion($id, $this->obtenerEmpresaId());

        return view('Sistema.pages.empresa.factura-carta', compact('venta'));
    }

    public function imprimirTicket(string $id): View
    {
        $venta = $this->ventaService->obtenerVentaParaImpresion($id, $this->obtenerEmpresaId());

        return view('Sistema.pages.empresa.ticket-venta', compact('venta'));
    }
}
