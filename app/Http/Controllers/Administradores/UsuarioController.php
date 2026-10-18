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
        $catalogos = $this->usuarioClass->obtenerRolesYEmpresas(Auth::user());

        return view('Sistema.pages.administradores.usuario', compact('catalogos'));
    }

    public function lista(): JsonResponse
    {
        $usuarios = $this->usuarioClass->lista(Auth::user());

        if (request()->has('draw')) {
            return datatables()->of($usuarios)
                ->filter(function ($instance) {
                    if ($search = request('search.value')) {
                        $search = mb_strtolower($search);
                        $instance->collection = $instance->collection->filter(function ($row) use ($search) {
                            $empresas = collect($row['empresas'] ?? [])->pluck('nombre')->implode(' ');
                            $nombreCompleto = trim(($row['nombre'] ?? '').' '.($row['apellido'] ?? ''));

                            return str_contains(mb_strtolower($row['name'] ?? ''), $search)
                                || str_contains(mb_strtolower($row['nombre'] ?? ''), $search)
                                || str_contains(mb_strtolower($row['apellido'] ?? ''), $search)
                                || str_contains(mb_strtolower($nombreCompleto), $search)
                                || str_contains(mb_strtolower($row['email'] ?? ''), $search)
                                || str_contains(mb_strtolower($row['rol'] ?? ''), $search)
                                || str_contains(mb_strtolower($empresas), $search);
                        });
                    }

                    if ($rol = request('rol')) {
                        $instance->collection = $instance->collection->filter(fn ($row) => ($row['rol'] ?? '') === $rol);
                    }

                    if ($empresa = request('empresa')) {
                        $instance->collection = $instance->collection->filter(function ($row) use ($empresa) {
                            if (($row['rol'] ?? '') === 'SuperAdmin') {
                                return true;
                            }
                            $empresas = collect($row['empresas'] ?? []);

                            return $empresas->contains(fn ($e) => strcasecmp($e['nombre'] ?? '', $empresa) === 0);
                        });
                    }
                })
                ->toJson();
        }

        return response()->json([
            'success' => true,
            'data' => $usuarios,
        ]);
    }

    public function catalogos(): JsonResponse
    {
        return response()->json($this->usuarioClass->obtenerRolesYEmpresas(Auth::user()));
    }

    public function detalle(int $id): JsonResponse
    {
        try {
            $usuario = $this->usuarioClass->detalle($id, Auth::user());

            return response()->json($usuario);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'autorización') ? 403 : 404;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Usuario no encontrado',
            ], $code);
        }
    }

    public function guardar(CrearRequest $request): JsonResponse
    {
        try {
            $usuario = $this->usuarioClass->guardar($request->validated(), Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado correctamente',
                'data' => $usuario,
            ]);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'autorización') ? 403 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }

    public function actualizar(ActualizarRequest $request, int $id): JsonResponse
    {
        try {
            $usuario = $this->usuarioClass->actualizar($request->validated(), $id, Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente',
                'data' => $usuario,
            ]);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'autorización') ? 403 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }
    }

    public function actualizarPermisos(ActualizarPermisosRequest $request, int $id): JsonResponse
    {
        try {
            $permisos = $request->validated('permisos', []);
            $usuario = $this->usuarioClass->actualizarPermisos($id, $permisos ?? [], Auth::user());

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
            $code = str_contains($e->getMessage(), 'autorización') || str_contains($e->getMessage(), 'modificar permisos') ? 403 : 500;

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar permisos: '.$e->getMessage(),
            ], $code);
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

            $usuario = $this->usuarioClass->eliminar($id, Auth::user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario eliminado correctamente',
                'data' => $usuario,
            ]);
        } catch (Exception $e) {
            $code = str_contains($e->getMessage(), 'autorización') ? 403 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
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
