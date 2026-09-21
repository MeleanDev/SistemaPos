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
        Schema::create('devoluciones_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('codigo', 50); // DEV-00001
            $table->text('motivo');
            $table->decimal('total_devuelto_usd', 16, 2)->default(0);
            $table->decimal('total_devuelto_bs', 16, 2)->default(0);
            $table->enum('tipo_reembolso', ['efectivo', 'metodo_original', 'credito_favor', 'sin_reembolso'])->default('sin_reembolso');
            $table->timestamps();

            $table->index(['empresa_id', 'venta_id']);
        });

        Schema::create('devolucion_venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('devolucion_venta_id')->constrained('devoluciones_venta')->onDelete('cascade');
            $table->foreignId('venta_detalle_id')->constrained('venta_detalles')->onDelete('restrict');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('restrict');
            $table->decimal('cantidad', 14, 3);
            $table->decimal('precio_unitario_usd', 16, 4);
            $table->decimal('precio_unitario_bs', 16, 4);
            $table->decimal('subtotal_usd', 16, 2);
            $table->decimal('subtotal_bs', 16, 2);
            $table->timestamps();

            $table->index(['devolucion_venta_id', 'producto_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devolucion_venta_detalles');
        Schema::dropIfExists('devoluciones_venta');
    }
};
