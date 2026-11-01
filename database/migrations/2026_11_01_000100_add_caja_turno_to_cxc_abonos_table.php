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
        if (Schema::hasTable('cuentas_por_cobrar_abonos')) {
            Schema::table('cuentas_por_cobrar_abonos', function (Blueprint $table) {
                if (! Schema::hasColumn('cuentas_por_cobrar_abonos', 'caja_id')) {
                    $table->foreignId('caja_id')->nullable()->after('metodo_pago_id')->constrained('cajas')->nullOnDelete();
                }
                if (! Schema::hasColumn('cuentas_por_cobrar_abonos', 'caja_turno_id')) {
                    $table->foreignId('caja_turno_id')->nullable()->after('caja_id')->constrained('caja_turnos')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('cuentas_por_cobrar_abonos')) {
            Schema::table('cuentas_por_cobrar_abonos', function (Blueprint $table) {
                if (Schema::hasColumn('cuentas_por_cobrar_abonos', 'caja_turno_id')) {
                    $table->dropForeign(['caja_turno_id']);
                    $table->dropColumn('caja_turno_id');
                }
                if (Schema::hasColumn('cuentas_por_cobrar_abonos', 'caja_id')) {
                    $table->dropForeign(['caja_id']);
                    $table->dropColumn('caja_id');
                }
            });
        }
    }
};
