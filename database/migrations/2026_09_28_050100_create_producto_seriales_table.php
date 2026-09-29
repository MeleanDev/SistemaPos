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
        if (! Schema::hasTable('producto_seriales')) {
            Schema::create('producto_seriales', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
                $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
                $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('restrict');
                $table->foreignId('recepcion_id')->nullable()->constrained('recepciones')->nullOnDelete();
                $table->foreignId('recepcion_detalle_id')->nullable()->constrained('recepcion_detalles')->nullOnDelete();
                $table->string('numero_serial', 100);
                $table->string('variante_color', 100)->nullable();
                $table->enum('estado', ['disponible', 'reservado', 'vendido', 'en_garantia', 'devuelto'])->default('disponible');
                $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
                $table->foreignId('venta_detalle_id')->nullable()->constrained('venta_detalles')->nullOnDelete();
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['empresa_id', 'producto_id', 'estado']);
                $table->index(['empresa_id', 'almacen_id', 'estado']);
                $table->index(['empresa_id', 'numero_serial']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_seriales');
    }
};
