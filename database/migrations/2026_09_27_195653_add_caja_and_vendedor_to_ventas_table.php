<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            if (! Schema::hasColumn('ventas', 'caja_id')) {
                $table->foreignId('caja_id')->nullable()->after('almacen_id')->constrained('cajas')->nullOnDelete();
            }
            if (! Schema::hasColumn('ventas', 'caja_turno_id')) {
                $table->foreignId('caja_turno_id')->nullable()->after('caja_id')->constrained('caja_turnos')->nullOnDelete();
            }
            if (! Schema::hasColumn('ventas', 'vendedor_id')) {
                $table->foreignId('vendedor_id')->nullable()->after('cliente_id')->constrained('vendedores')->nullOnDelete();
            }
            if (! Schema::hasColumn('ventas', 'comision_porcentaje')) {
                $table->decimal('comision_porcentaje', 5, 2)->default(0.00)->after('descuento_usd');
            }
            if (! Schema::hasColumn('ventas', 'comision_monto_usd')) {
                $table->decimal('comision_monto_usd', 14, 2)->default(0.00)->after('comision_porcentaje');
            }
            if (! Schema::hasColumn('ventas', 'comision_monto_bs')) {
                $table->decimal('comision_monto_bs', 14, 2)->default(0.00)->after('comision_monto_usd');
            }
        });

        if (Schema::hasTable('devoluciones_venta') && ! Schema::hasColumn('devoluciones_venta', 'caja_turno_id')) {
            Schema::table('devoluciones_venta', function (Blueprint $table) {
                $table->foreignId('caja_turno_id')->nullable()->after('venta_id')->constrained('caja_turnos')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('devoluciones_venta') && Schema::hasColumn('devoluciones_venta', 'caja_turno_id')) {
            Schema::table('devoluciones_venta', function (Blueprint $table) {
                $table->dropForeign(['caja_turno_id']);
                $table->dropColumn('caja_turno_id');
            });
        }

        Schema::table('ventas', function (Blueprint $table) {
            if (Schema::hasColumn('ventas', 'caja_id')) {
                $table->dropForeign(['caja_id']);
                $table->dropColumn('caja_id');
            }
            if (Schema::hasColumn('ventas', 'caja_turno_id')) {
                $table->dropForeign(['caja_turno_id']);
                $table->dropColumn('caja_turno_id');
            }
            if (Schema::hasColumn('ventas', 'vendedor_id')) {
                $table->dropForeign(['vendedor_id']);
                $table->dropColumn('vendedor_id');
            }
            if (Schema::hasColumn('ventas', 'comision_porcentaje')) {
                $table->dropColumn('comision_porcentaje');
            }
            if (Schema::hasColumn('ventas', 'comision_monto_usd')) {
                $table->dropColumn('comision_monto_usd');
            }
            if (Schema::hasColumn('ventas', 'comision_monto_bs')) {
                $table->dropColumn('comision_monto_bs');
            }
        });
    }
};
