<?php

namespace App\Service\Administradores;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsuarioClass
{
    /**
     * Listado de usuarios activos con roles y empresas
     */
    public function lista(): Collection
    {
        return User::with(['roles', 'empresas', 'permissions'])
            ->select('id', 'name', 'nombre', 'apellido', 'email', 'estado', 'created_at')
            ->where('estado', true)
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($usuario) {
                return [
                    'id' => $usuario->id,
                    'name' => $usuario->name,
                    'nombre' => $usuario->nombre,
                    'apellido' => $usuario->apellido,
                    'email' => $usuario->email,
                    'rol' => $usuario->roles->pluck('name')->first() ?? 'Sin Rol',
                    'empresas' => $usuario->empresas->map(fn ($empresa) => [
                        'id' => $empresa->id,
                        'nombre' => $empresa->nombre,
                        'rif' => $empresa->rif,
                    ])->values()->all(),
                    'permisos' => $usuario->permissions->pluck('name')->all(),
                    'created_at' => $usuario->created_at?->format('d/m/Y') ?? '',
                ];
            });
    }

    /**
     * Detalle completo de un usuario
     */
    public function detalle(int $id): array
    {
        $usuario = User::with(['roles', 'empresas', 'permissions'])->findOrFail($id);

        return [
            'id' => $usuario->id,
            'name' => $usuario->name,
            'nombre' => $usuario->nombre,
            'apellido' => $usuario->apellido,
            'email' => $usuario->email,
            'rol' => $usuario->roles->pluck('name')->first() ?? 'Operador',
            'empresas' => $usuario->empresas->pluck('id')->toArray(),
            'permisos' => $usuario->permissions->pluck('name')->toArray(),
            'estado' => $usuario->estado,
        ];
    }

    /**
     * Catálogos para formularios de usuarios y permisos
     */
    public function obtenerRolesYEmpresas(): array
    {
        return [
            'roles' => Role::where('guard_name', 'web')->get(['id', 'name']),
            'empresas' => Empresa::where('estado', true)->orderBy('nombre')->get(['id', 'nombre', 'rif', 'razon_social']),
            'permisos_modulos' => [
                [
                    'modulo' => 'Clientes',
                    'icono' => 'fas fa-users text-info',
                    'descripcion' => 'Directorio y ficha de clientes',
                    'permisos' => [
                        ['name' => 'clientes.ver', 'label' => 'Ver listado y detalles'],
                        ['name' => 'clientes.crear', 'label' => 'Registrar clientes'],
                        ['name' => 'clientes.editar', 'label' => 'Editar información'],
                        ['name' => 'clientes.eliminar', 'label' => 'Eliminar / Desactivar'],
                    ],
                ],
                [
                    'modulo' => 'Proveedores',
                    'icono' => 'fas fa-truck-moving text-warning',
                    'descripcion' => 'Gestión de proveedores y compras',
                    'permisos' => [
                        ['name' => 'proveedores.ver', 'label' => 'Ver listado y detalles'],
                        ['name' => 'proveedores.crear', 'label' => 'Registrar proveedores'],
                        ['name' => 'proveedores.editar', 'label' => 'Editar información'],
                        ['name' => 'proveedores.eliminar', 'label' => 'Eliminar / Desactivar'],
                    ],
                ],
                [
                    'modulo' => 'Métodos de Pago',
                    'icono' => 'fas fa-credit-card text-success',
                    'descripcion' => 'Formas y pasarelas de cobro',
                    'permisos' => [
                        ['name' => 'metodos_pago.ver', 'label' => 'Ver formas de pago'],
                        ['name' => 'metodos_pago.crear', 'label' => 'Crear nuevas formas'],
                        ['name' => 'metodos_pago.editar', 'label' => 'Editar formas'],
                        ['name' => 'metodos_pago.eliminar', 'label' => 'Eliminar formas'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Guardar nuevo usuario o reactivar existente
     */
    public function guardar(array $datos): User
    {
        $rol = $datos['rol'] ?? 'Operador';
        $empresas = $datos['empresas'] ?? [];
        $permisos = $datos['permisos'] ?? [];

        // Comprobar si existía inactivo para reactivación
        $existenteInactivo = User::where('name', $datos['name'])
            ->orWhere('email', $datos['email'])
            ->first();

        if ($existenteInactivo) {
            $datos['estado'] = true;
            if (! empty($datos['password'])) {
                $datos['password'] = Hash::make($datos['password']);
            } else {
                unset($datos['password']);
            }

            $existenteInactivo->update($datos);
            $existenteInactivo->syncRoles($rol);

            if ($rol === 'SuperAdmin') {
                $existenteInactivo->empresas()->detach();
                $existenteInactivo->syncPermissions([]);
            } elseif ($rol === 'Admin') {
                $existenteInactivo->empresas()->sync($empresas);
                $existenteInactivo->syncPermissions([]);
            } else { // Operador
                $existenteInactivo->empresas()->sync($empresas);
                $existenteInactivo->syncPermissions($permisos);
            }

            return $existenteInactivo;
        }

        $datos['estado'] = true;
        $datos['password'] = Hash::make($datos['password']);

        $usuario = User::create($datos);
        $usuario->syncRoles($rol);

        if ($rol === 'SuperAdmin') {
            $usuario->empresas()->detach();
            $usuario->syncPermissions([]);
        } elseif ($rol === 'Admin') {
            $usuario->empresas()->sync($empresas);
            $usuario->syncPermissions([]);
        } else { // Operador
            if (! empty($empresas)) {
                $usuario->empresas()->sync($empresas);
            }
            $usuario->syncPermissions($permisos);
        }

        return $usuario;
    }

    /**
     * Actualizar datos y asignaciones de un usuario
     */
    public function actualizar(array $datos, int $id): User
    {
        $usuario = User::findOrFail($id);
        $rol = $datos['rol'] ?? 'Operador';
        $empresas = $datos['empresas'] ?? [];
        $permisos = $datos['permisos'] ?? [];

        if (! empty($datos['password'])) {
            $datos['password'] = Hash::make($datos['password']);
        } else {
            unset($datos['password']);
        }

        $usuario->update($datos);
        $usuario->syncRoles($rol);

        if ($rol === 'SuperAdmin') {
            $usuario->empresas()->detach();
            $usuario->syncPermissions([]);
        } elseif ($rol === 'Admin') {
            $usuario->empresas()->sync($empresas);
            $usuario->syncPermissions([]);
        } else { // Operador
            $usuario->empresas()->sync($empresas);
            $usuario->syncPermissions($permisos);
        }

        return $usuario;
    }

    /**
     * Actualizar permisos granulares de un operador
     */
    public function actualizarPermisos(int $id, array $permisos): User
    {
        $usuario = User::findOrFail($id);
        $usuario->syncPermissions($permisos);

        return $usuario;
    }

    /**
     * Borrado lógico de un usuario
     */
    public function eliminar(int $id): User
    {
        $usuario = User::findOrFail($id);
        $usuario->estado = false;
        $usuario->save();

        return $usuario;
    }

    /**
     * Cambiar empresa activa en sesión
     */
    public function cambiarEmpresaActiva(int $empresaId, User $user): bool
    {
        $empresa = Empresa::where('id', $empresaId)->where('estado', true)->first();

        if (! $empresa) {
            return false;
        }

        if ($user->hasRole('SuperAdmin')) {
            session(['empresa_activa_id' => $empresa->id]);

            return true;
        }

        $tieneAcceso = $user->empresas()
            ->wherePivot('estado', true)
            ->where('empresas.id', $empresa->id)
            ->exists();

        if ($tieneAcceso) {
            session(['empresa_activa_id' => $empresa->id]);

            return true;
        }

        return false;
    }
}
