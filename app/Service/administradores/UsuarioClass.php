<?php

namespace App\Service\Administradores;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
    public function obtenerRolesYEmpresas(?User $usuarioAutenticado = null): array
    {
        $usuario = $usuarioAutenticado ?? Auth::user();
        $esSuperAdmin = $usuario ? $usuario->hasRole('SuperAdmin') : true;

        $rolesQuery = Role::where('guard_name', 'web');
        if (! $esSuperAdmin) {
            $rolesQuery->whereIn('name', ['Admin', 'Operador']);
        }
        $roles = $rolesQuery->get(['id', 'name']);

        if (! $esSuperAdmin && $usuario) {
            $empresas = $usuario->empresas()
                ->wherePivot('estado', true)
                ->where('empresas.estado', true)
                ->orderBy('nombre')
                ->get(['empresas.id', 'empresas.nombre', 'empresas.rif', 'empresas.razon_social']);
        } else {
            $empresas = Empresa::where('estado', true)->orderBy('nombre')->get(['id', 'nombre', 'rif', 'razon_social']);
        }

        return [
            'roles' => $roles,
            'empresas' => $empresas,
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
                [
                    'modulo' => 'Categorías',
                    'icono' => 'fas fa-tags text-primary',
                    'descripcion' => 'Clasificación y rubros de inventario',
                    'permisos' => [
                        ['name' => 'categorias.ver', 'label' => 'Ver categorías'],
                        ['name' => 'categorias.crear', 'label' => 'Crear categorías'],
                        ['name' => 'categorias.editar', 'label' => 'Editar categorías'],
                        ['name' => 'categorias.eliminar', 'label' => 'Eliminar / Desactivar'],
                    ],
                ],
                [
                    'modulo' => 'Servicios',
                    'icono' => 'fas fa-concierge-bell text-info',
                    'descripcion' => 'Catálogo de servicios y mano de obra',
                    'permisos' => [
                        ['name' => 'servicios.ver', 'label' => 'Ver catálogo de servicios'],
                        ['name' => 'servicios.crear', 'label' => 'Registrar servicios'],
                        ['name' => 'servicios.editar', 'label' => 'Editar información y precios'],
                        ['name' => 'servicios.eliminar', 'label' => 'Eliminar / Desactivar'],
                    ],
                ],
                [
                    'modulo' => 'Productos & Inventario',
                    'icono' => 'fas fa-boxes-stacked text-primary',
                    'descripcion' => 'Catálogo de artículos y códigos de barra',
                    'permisos' => [
                        ['name' => 'productos.ver', 'label' => 'Ver catálogo de productos'],
                        ['name' => 'productos.crear', 'label' => 'Registrar nuevos productos'],
                        ['name' => 'productos.editar', 'label' => 'Editar ficha técnica y precios'],
                        ['name' => 'productos.eliminar', 'label' => 'Eliminar / Desactivar'],
                    ],
                ],
                [
                    'modulo' => 'Almacenes & Sedes',
                    'icono' => 'fas fa-warehouse text-secondary',
                    'descripcion' => 'Depósitos, bodegas y sucursales',
                    'permisos' => [
                        ['name' => 'almacenes.ver', 'label' => 'Ver listado de almacenes'],
                        ['name' => 'almacenes.crear', 'label' => 'Crear almacenes'],
                        ['name' => 'almacenes.editar', 'label' => 'Editar información de sede'],
                        ['name' => 'almacenes.eliminar', 'label' => 'Eliminar almacenes'],
                    ],
                ],
                [
                    'modulo' => 'Motos & Seriales',
                    'icono' => 'fas fa-motorcycle text-danger',
                    'descripcion' => 'Control de vehículos, NIV, Chasis y Motor',
                    'permisos' => [
                        ['name' => 'motos.ver', 'label' => 'Ver catálogo de motos'],
                        ['name' => 'motos.crear', 'label' => 'Registrar datos de motos'],
                        ['name' => 'motos.editar', 'label' => 'Editar seriales y ficha'],
                        ['name' => 'motos.eliminar', 'label' => 'Eliminar / Desactivar'],
                        ['name' => 'motos.recepcion', 'label' => 'Recepción por lotes y seriales'],
                    ],
                ],
                [
                    'modulo' => 'Recepción & Movimientos',
                    'icono' => 'fas fa-dolly-flatbed text-success',
                    'descripcion' => 'Ingreso de mercancía, traslados y kardex',
                    'permisos' => [
                        ['name' => 'compras.recepcion', 'label' => 'Registrar recepciones de mercancía'],
                        ['name' => 'inventario.kardex', 'label' => 'Consultar libro mayor de Kardex'],
                        ['name' => 'inventario.traslados', 'label' => 'Realizar traslados entre almacenes'],
                        ['name' => 'inventario.ajustar_stock', 'label' => 'Realizar ajustes de inventario'],
                    ],
                ],
                [
                    'modulo' => 'Punto de Venta (POS)',
                    'icono' => 'fas fa-cash-register text-success',
                    'descripcion' => 'Facturación rápida, tickets y cobros',
                    'permisos' => [
                        ['name' => 'pos.acceso', 'label' => 'Acceso al Punto de Venta (POS)'],
                        ['name' => 'ventas.ver', 'label' => 'Ver historial de ventas y facturas'],
                        ['name' => 'ventas.crear', 'label' => 'Procesar y facturar ventas'],
                        ['name' => 'ventas.anular', 'label' => 'Anular ventas y devoluciones'],
                        ['name' => 'ventas.descuentos', 'label' => 'Aplicar descuentos en ventas'],
                    ],
                ],
                [
                    'modulo' => 'Cajas & Turnos',
                    'icono' => 'fas fa-vault text-warning',
                    'descripcion' => 'Aperturas, arqueos y cierres de turno',
                    'permisos' => [
                        ['name' => 'cajas.ver', 'label' => 'Ver estado de cajas'],
                        ['name' => 'cajas.aperturar', 'label' => 'Aperturar turnos de caja'],
                        ['name' => 'cajas.cerrar', 'label' => 'Cerrar caja y arqueo final'],
                        ['name' => 'cajas.movimientos', 'label' => 'Registrar entradas/salidas de caja'],
                        ['name' => 'cajas.arqueo', 'label' => 'Realizar arqueos de efectivo'],
                    ],
                ],
                [
                    'modulo' => 'Cuentas por Cobrar (CXC)',
                    'icono' => 'fas fa-file-invoice-dollar text-primary',
                    'descripcion' => 'Gestión de créditos de clientes y abonos',
                    'permisos' => [
                        ['name' => 'cxc.ver', 'label' => 'Ver cuentas por cobrar'],
                        ['name' => 'cxc.abonar', 'label' => 'Registrar abonos de clientes'],
                    ],
                ],
                [
                    'modulo' => 'Cuentas por Pagar (CXP)',
                    'icono' => 'fas fa-hand-holding-usd text-danger',
                    'descripcion' => 'Control de deudas con proveedores',
                    'permisos' => [
                        ['name' => 'cxp.ver', 'label' => 'Ver cuentas por pagar'],
                        ['name' => 'cxp.abonar', 'label' => 'Registrar abonos a proveedores'],
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
