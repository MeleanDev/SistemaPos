<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cliente\ActualizarRequest;
use App\Http\Requests\Cliente\CrearRequest;
use App\Service\Empresa\ClienteClass;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function __construct(private ClienteClass $clienteClass) {}

    public function index(): View
    {
        return view('Sistema.pages.empresa.cliente');
    }

    public function lista(): JsonResponse
    {
        $clientes = $this->clienteClass->lista();

        return datatables()->of($clientes)
            ->filter(function ($query) {
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->whereRaw("CONCAT(nombre, ' ', apellido) LIKE ?", ["%{$search}%"])
                            ->orWhere('cedula', 'LIKE', "%{$search}%")
                            ->orWhere('telefono', 'LIKE', "%{$search}%")
                            ->orWhere('correo', 'LIKE', "%{$search}%")
                            ->orWhere('tipo_cliente', 'LIKE', "%{$search}%");
                    });
                }
            })
            ->toJson();
    }

    public function detalle($id): JsonResponse
    {
        try {
            $cliente = $this->clienteClass->detalle($id);

            return response()->json($cliente);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Cliente no encontrado',
            ], 404);
        }
    }

    public function guardar(CrearRequest $datos): JsonResponse
    {
        try {
            $cliente = $this->clienteClass->guardar($datos->validated());

            return response()->json([
                'success' => true,
                'message' => 'Cliente registrado correctamente',
                'data' => $cliente,
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
            $cliente = $this->clienteClass->actualizar($datos->validated(), $id);

            return response()->json([
                'success' => true,
                'message' => 'Cliente actualizado correctamente',
                'data' => $cliente,
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
            $cliente = $this->clienteClass->eliminar($id);
            $estadoTexto = $cliente->estado ? 'activado' : 'desactivado';

            return response()->json([
                'success' => true,
                'message' => "Cliente {$estadoTexto} correctamente",
                'data' => $cliente,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
