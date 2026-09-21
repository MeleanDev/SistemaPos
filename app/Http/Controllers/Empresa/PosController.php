<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Service\Ventas\VentaClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        protected VentaClass $ventaService
    ) {}

    /**
     * Renderizar la interfaz interactiva de Punto de Venta (POS)
     */
    public function index(): View
    {
        return view('Sistema.pages.empresa.pos');
    }

    /**
     * Retornar catálogos y datos iniciales en formato JSON
     */
    public function datos(): JsonResponse
    {
        try {
            $datos = $this->ventaService->datosInicialesPos();

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

    /**
     * Búsqueda en tiempo real de clientes
     */
    public function buscarClientes(Request $request): JsonResponse
    {
        $termino = (string) $request->input('termino', '');
        $clientes = $this->ventaService->buscarClientes($termino);

        return response()->json([
            'success' => true,
            'data' => $clientes,
        ]);
    }

    /**
     * Registro rápido de cliente desde el POS
     */
    public function guardarClienteRapido(Request $request): JsonResponse
    {
        $request->validate([
            'cedula' => ['required', 'string', 'max:20'],
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:25'],
            'correo' => ['nullable', 'email', 'max:150'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'tipo_cliente' => ['nullable', 'string', 'in:detal,mayorista'],
        ]);

        try {
            $cliente = $this->ventaService->guardarClienteRapido($request->all());

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

    /**
     * Procesar y facturar la venta
     */
    public function guardar(Request $request): JsonResponse
    {
        $request->validate([
            'cliente_id' => ['required', 'integer', 'exists:clientes,id'],
            'almacen_id' => ['required', 'integer', 'exists:almacenes,id'],
            'tipo_venta' => ['required', 'string', 'in:detal,mayor'],
            'tasa_cambio' => ['required', 'numeric', 'min:0.0001'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.001'],
            'items.*.precio_unitario_usd' => ['required', 'numeric', 'min:0.0001'],
            'pagos' => ['nullable', 'array'],
        ]);

        try {
            $venta = $this->ventaService->procesarVenta($request->all());

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

    /**
     * Guardar venta en espera (pausada)
     */
    public function guardarEnEspera(Request $request): JsonResponse
    {
        try {
            $espera = $this->ventaService->guardarEnEspera($request->all());

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

    /**
     * Listar ventas en espera
     */
    public function listarEnEspera(): JsonResponse
    {
        try {
            $lista = $this->ventaService->listarEnEspera();

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

    /**
     * Recuperar venta en espera
     */
    public function recuperarEnEspera(int $id): JsonResponse
    {
        try {
            $datos = $this->ventaService->recuperarEnEspera($id);

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

    /**
     * Eliminar venta en espera
     */
    public function eliminarEnEspera(int $id): JsonResponse
    {
        try {
            $this->ventaService->eliminarEnEspera($id);

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

    /**
     * Buscar factura para devolución
     */
    public function buscarFacturaDevolucion(Request $request): JsonResponse
    {
        $busqueda = (string) $request->input('busqueda', '');

        try {
            $venta = $this->ventaService->buscarFacturaDevolucion($busqueda);

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

    /**
     * Procesar devolución de venta
     */
    public function procesarDevolucion(Request $request): JsonResponse
    {
        $request->validate([
            'venta_id' => ['required', 'integer'],
            'motivo' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.venta_detalle_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.001'],
        ]);

        try {
            $devolucion = $this->ventaService->procesarDevolucion($request->all());

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

    /**
     * Vista para imprimir ticket térmico
     */
    public function imprimir(string $id): View
    {
        $venta = $this->ventaService->obtenerVentaParaImpresion($id);

        return view('Sistema.pages.empresa.ticket-venta', compact('venta'));
    }
}
