<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $permisosReportes = [
                'reportes.ver',
                'reportes.financieros',
                'reportes.inventario',
                'reportes.vendedores',
            ];

            foreach ($permisosReportes as $permiso) {
                Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
            }

            $superAdminRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
            $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);

            $superAdminRole->givePermissionTo($permisosReportes);
            $adminRole->givePermissionTo($permisosReportes);

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            Permission::whereIn('name', ['reportes.financieros', 'reportes.inventario'])->delete();
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }
    }
};
