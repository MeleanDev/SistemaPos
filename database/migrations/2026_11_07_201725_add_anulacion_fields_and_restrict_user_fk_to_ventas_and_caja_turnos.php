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
            if (! Schema::hasColumn('ventas', 'anulada_por_id')) {
                $table->foreignId('anulada_por_id')->nullable()->after('estado')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('ventas', 'anulada_at')) {
                $table->timestamp('anulada_at')->nullable()->after('anulada_por_id');
            }
            if (! Schema::hasColumn('ventas', 'motivo_anulacion')) {
                $table->string('motivo_anulacion', 500)->nullable()->after('anulada_at');
            }
        });

        Schema::table('devoluciones_venta', function (Blueprint $table) {
            $table->string('tipo_reembolso', 30)->default('sin_reembolso')->change();
            if (! Schema::hasColumn('devoluciones_venta', 'metodo_pago_id')) {
                $table->foreignId('metodo_pago_id')->nullable()->after('tipo_reembolso')->constrained('metodos_pago')->nullOnDelete();
            }
        });

        Schema::table('caja_turnos', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caja_turnos', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('devoluciones_venta', function (Blueprint $table) {
            if (Schema::hasColumn('devoluciones_venta', 'metodo_pago_id')) {
                $table->dropConstrainedForeignId('metodo_pago_id');
            }
        });

        Schema::table('ventas', function (Blueprint $table) {
            if (Schema::hasColumn('ventas', 'anulada_por_id')) {
                $table->dropConstrainedForeignId('anulada_por_id');
            }
            if (Schema::hasColumn('ventas', 'anulada_at')) {
                $table->dropColumn('anulada_at');
            }
            if (Schema::hasColumn('ventas', 'motivo_anulacion')) {
                $table->dropColumn('motivo_anulacion');
            }
        });
    }
};
