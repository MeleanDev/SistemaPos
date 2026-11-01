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
        Schema::table('caja_movimientos', function (Blueprint $table) {
            if (! Schema::hasColumn('caja_movimientos', 'metodo_pago_id')) {
                $table->foreignId('metodo_pago_id')->nullable()->after('tipo')->constrained('metodos_pago')->nullOnDelete();
            }
            if (! Schema::hasColumn('caja_movimientos', 'moneda')) {
                $table->string('moneda', 10)->default('USD')->after('metodo_pago_id');
            }
            if (! Schema::hasColumn('caja_movimientos', 'documento_tipo')) {
                $table->string('documento_tipo', 50)->nullable()->after('observaciones');
            }
            if (! Schema::hasColumn('caja_movimientos', 'documento_id')) {
                $table->unsignedBigInteger('documento_id')->nullable()->after('documento_tipo');
                $table->index(['documento_tipo', 'documento_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('caja_movimientos', function (Blueprint $table) {
            if (Schema::hasColumn('caja_movimientos', 'documento_id')) {
                $table->dropIndex(['documento_tipo', 'documento_id']);
                $table->dropColumn('documento_id');
            }
            if (Schema::hasColumn('caja_movimientos', 'documento_tipo')) {
                $table->dropColumn('documento_tipo');
            }
            if (Schema::hasColumn('caja_movimientos', 'moneda')) {
                $table->dropColumn('moneda');
            }
            if (Schema::hasColumn('caja_movimientos', 'metodo_pago_id')) {
                $table->dropConstrainedForeignId('metodo_pago_id');
            }
        });
    }
};
