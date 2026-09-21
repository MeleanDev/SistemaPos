<?php

namespace App\Http\Controllers\Administradores;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\ActualizarPermisosRequest;
use App\Http\Requests\Usuario\ActualizarRequest;
use App\Http\Requests\Usuario\CambiarEmpresaRequest;
use App\Http\Requests\Usuario\CrearRequest;
use App\Service\Administradores\UsuarioClass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function __construct(private UsuarioClass $usuarioClass) {}

    public function index(): View
    {
        $catalogos = $this->usuarioClass->obtenerRolesYEmpresas();

        return view('Sistema.pages.administradores.usuario', compact('catalogos'));
    }

    public function lista(): JsonResponse
    {
        $usuarios = $this->usuarioClass->lista();

        return response()->json([
            'success' => true,
            'data' => $usuarios,
        ]);
    }

    public function catalogos(): JsonResponse
    {
        return response()->json($this->usuarioClass->obtenerRolesYEmpresas());
    }

    public function detalle(int $id): JsonResponse
    {
        try {
            $usuario = $this->usuarioClass->detalle($id);

            return response()->json($usuario);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }
    }

    public function guardar(CrearRequest $request): JsonResponse
    {
        try {
            $usuario = $this->usuarioClass->guardar($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado correctamente',
                'data' => $usuario,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function actualizar(ActualizarRequest $request, int $id): JsonResponse
    {
        try {
            $usuario = $this->usuarioClass->actualizar($request->validated(), $id);

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'data' => $usuario,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function actualizarPermisos(ActualizarPermisosRequest $request, int $id): JsonResponse
    {
        try {
            $permisos = $request->validated('permisos', []);
            $usuario = $this->usuarioClass->actualizarPermisos($id, $permisos ?? []);

            return response()->json([
                'success' => true,
                'message' => 'Permisos modulares actualizados exitosamente.',
                'data' => [
                    'id' => $usuario->id,
                    'permisos' => $usuario->permissions->pluck('name')->all(),
                    'permisos_count' => $usuario->permissions->count(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar permisos: '.$e->getMessage(),
            ], 500);
        }
    }

    public function eliminar(int $id): JsonResponse
    {
        try {
            if (Auth::id() == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No puedes eliminar tu propia cuenta de usuario en sesión.',
                ], 400);
            }

            $usuario = $this->usuarioClass->eliminar($id);

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado correctamente',
                'data' => $usuario,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function cambiarEmpresa(CambiarEmpresaRequest $request): JsonResponse
    {
        $empresaId = (int) $request->validated('empresa_id');
        $cambiado = $this->usuarioClass->cambiarEmpresaActiva($empresaId, Auth::user());

        if ($cambiado) {
            return response()->json([
                'success' => true,
                'message' => 'Empresa activa cambiada correctamente.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para acceder a esta empresa.',
        ], 403);
    }
}
