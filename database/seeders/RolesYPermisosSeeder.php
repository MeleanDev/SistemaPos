<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesYPermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar caché de permisos en Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Matriz de Permisos por Módulo
        $permisos = [
            // Empresas (Multi-Empresa)
            'empresas.ver',
            'empresas.crear',
            'empresas.editar',
            'empresas.eliminar',

            // Almacenes
            'almacenes.ver',
            'almacenes.crear',
            'almacenes.editar',
            'almacenes.eliminar',

            // Seguridad, Usuarios y Roles
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',
            'usuarios.permisos',
            'usuarios.asignar_empresas',

            // Clientes
            'clientes.ver',
            'clientes.crear',
            'clientes.editar',
            'clientes.eliminar',

            // Proveedores
            'proveedores.ver',
            'proveedores.crear',
            'proveedores.editar',
            'proveedores.eliminar',

            // Métodos de Pago
            'metodos_pago.ver',
            'metodos_pago.crear',
            'metodos_pago.editar',
            'metodos_pago.eliminar',

            // Inventario, Productos & Kardex
            'productos.ver',
            'productos.crear',
            'productos.editar',
            'productos.eliminar',
            'inventario.ajustar_stock',
            'inventario.kardex',
            'inventario.traslados',
            'compras.recepcion',

            // Cajas y Turnos
            'cajas.ver',
            'cajas.aperturar',
            'cajas.cerrar',
            'cajas.movimientos',
            'cajas.arqueo',

            // Punto de Venta (POS) y Facturación
            'pos.acceso',
            'ventas.ver',
            'ventas.crear',
            'ventas.anular',
            'ventas.descuentos',

            // Créditos (CXC / CXP)
            'cxc.ver',
            'cxc.abonar',
            'cxp.ver',
            'cxp.abonar',

            // Reportes y Auditoría
            'reportes.ver',
            'logs.ver',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // 2. Creación de Roles

        // A. SuperAdmin: Acceso global total
        $superAdminRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // B. Admin: Acceso integral a su empresa asignada
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminPermissions = [
            'almacenes.ver',
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',
            'usuarios.permisos',
            'clientes.ver',
            'clientes.crear',
            'clientes.editar',
            'clientes.eliminar',
            'proveedores.ver',
            'proveedores.crear',
            'proveedores.editar',
            'proveedores.eliminar',
            'metodos_pago.ver',
            'productos.ver',
            'productos.crear',
            'productos.editar',
            'productos.eliminar',
            'inventario.ajustar_stock',
            'inventario.kardex',
            'inventario.traslados',
            'compras.recepcion',
            'cajas.ver',
            'cajas.aperturar',
            'cajas.cerrar',
            'cajas.movimientos',
            'cajas.arqueo',
            'pos.acceso',
            'ventas.ver',
            'ventas.crear',
            'ventas.anular',
            'ventas.descuentos',
            'cxc.ver',
            'cxc.abonar',
            'cxp.ver',
            'cxp.abonar',
            'reportes.ver',
            'logs.ver',
        ];
        $adminRole->syncPermissions($adminPermissions);

        // C. Operador: Acceso operativo limitado a POS, Cajas, Clientes y Ventas
        $operadorRole = Role::firstOrCreate(['name' => 'Operador', 'guard_name' => 'web']);
        $operadorPermissions = [
            'pos.acceso',
            'ventas.ver',
            'ventas.crear',
            'clientes.ver',
            'clientes.crear',
            'cajas.ver',
            'cajas.aperturar',
            'cajas.cerrar',
            'cajas.movimientos',
            'cxc.ver',
            'cxc.abonar',
            'productos.ver',
            'metodos_pago.ver',
        ];
        $operadorRole->syncPermissions($operadorPermissions);
    }
}
