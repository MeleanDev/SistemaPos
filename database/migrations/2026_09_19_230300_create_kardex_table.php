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
        Schema::create('kardex', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('tipo_movimiento', 50); // entrada_recepcion, salida_venta, traslado_salida, traslado_entrada, ajuste_positivo, ajuste_negativo, anulacion_recepcion, anulacion_venta
            $table->string('documento_tipo', 50); // recepcion, venta, traslado, ajuste
            $table->unsignedBigInteger('documento_id');
            $table->decimal('cantidad', 14, 3);
            $table->decimal('costo_unitario_usd', 16, 4)->default(0);
            $table->decimal('costo_unitario_bs', 16, 4)->default(0);
            $table->decimal('stock_anterior', 14, 3);
            $table->decimal('stock_nuevo', 14, 3);
            $table->string('motivo', 255)->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'producto_id', 'almacen_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kardex');
    }
};
