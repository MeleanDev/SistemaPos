<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        // 1. B-20 & B-21: Permisos reales y rol Vendedor
        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            $permisosRequeridos = [
                'pos.acceso',
                'pos.preventa',
                'vendedores.ver',
                'vendedores.liquidar',
                'reportes.vendedores',
            ];

            foreach ($permisosRequeridos as $permiso) {
                Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
            }

            Permission::where('name', 'cajas.administrar_todas')->delete();

            $superAdminRole = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
            $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'Operador', 'guard_name' => 'web']);
            $vendedorRole = Role::firstOrCreate(['name' => 'Vendedor', 'guard_name' => 'web']);

            $superAdminRole->givePermissionTo(['pos.acceso', 'pos.preventa', 'vendedores.ver', 'vendedores.liquidar', 'reportes.vendedores']);
            $adminRole->givePermissionTo(['pos.acceso', 'pos.preventa', 'vendedores.ver', 'vendedores.liquidar', 'reportes.vendedores']);
            $vendedorRole->syncPermissions(['pos.acceso', 'pos.preventa', 'reportes.vendedores']);

            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        }

        // 2. B-24: Limpiar duplicados (empresa_id, user_id) en vendedores y agregar unique
        if (Schema::hasTable('vendedores')) {
            $duplicados = DB::table('vendedores')
                ->whereNotNull('user_id')
                ->select('empresa_id', 'user_id', DB::raw('MAX(id) as max_id'), DB::raw('COUNT(*) as total'))
                ->groupBy('empresa_id', 'user_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicados as $dup) {
                DB::table('vendedores')
                    ->where('empresa_id', $dup->empresa_id)
                    ->where('user_id', $dup->user_id)
                    ->where('id', '<', $dup->max_id)
                    ->update(['user_id' => null]);
            }

            Schema::table('vendedores', function (Blueprint $table) {
                $table->unique(['empresa_id', 'user_id'], 'vendedores_empresa_id_user_id_unique');
            });
        }

        // 3. B-25: Tabla liquidaciones_comision y campos en ventas
        if (! Schema::hasTable('liquidaciones_comision')) {
            Schema::create('liquidaciones_comision', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->foreignId('vendedor_id')->constrained('vendedores')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('caja_turno_id')->nullable()->constrained('caja_turnos')->nullOnDelete();
                $table->foreignId('metodo_pago_id')->nullable()->constrained('metodos_pago')->nullOnDelete();
                $table->string('codigo', 50);
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->unsignedInteger('cantidad_ventas')->default(0);
                $table->decimal('total_comision_usd', 14, 2)->default(0);
                $table->decimal('total_comision_bs', 14, 2)->default(0);
                $table->boolean('pagado_por_caja')->default(false);
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->unique(['empresa_id', 'codigo'], 'liquidaciones_comision_empresa_id_codigo_unique');
                $table->index(['empresa_id', 'vendedor_id']);
            });
        }

        if (Schema::hasTable('ventas')) {
            Schema::table('ventas', function (Blueprint $table) {
                if (! Schema::hasColumn('ventas', 'comision_estado')) {
                    $table->string('comision_estado', 20)->default('pendiente')->after('comision_monto_bs');
                }
                if (! Schema::hasColumn('ventas', 'liquidacion_comision_id')) {
                    $table->foreignId('liquidacion_comision_id')
                        ->nullable()
                        ->after('comision_estado')
                        ->constrained('liquidaciones_comision')
                        ->nullOnDelete();
                }
            });

            DB::table('ventas')
                ->where(function ($q) {
                    $q->whereNull('vendedor_id')
                        ->orWhere('comision_monto_usd', '<=', 0)
                        ->orWhereIn('estado', ['anulada', 'devuelta_total']);
                })
                ->update(['comision_estado' => 'sin_comision']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ventas')) {
            Schema::table('ventas', function (Blueprint $table) {
                if (Schema::hasColumn('ventas', 'liquidacion_comision_id')) {
                    $table->dropConstrainedForeignId('liquidacion_comision_id');
                }
                if (Schema::hasColumn('ventas', 'comision_estado')) {
                    $table->dropColumn('comision_estado');
                }
            });
        }

        Schema::dropIfExists('liquidaciones_comision');

        if (Schema::hasTable('vendedores')) {
            Schema::table('vendedores', function (Blueprint $table) {
                $table->dropUnique('vendedores_empresa_id_user_id_unique');
            });
        }
    }
};
