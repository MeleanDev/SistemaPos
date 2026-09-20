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
        Schema::create('recepcion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_id')->constrained('recepciones')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');
            $table->decimal('cantidad', 14, 3);
            $table->decimal('costo_anterior_usd', 16, 4)->default(0);
            $table->decimal('costo_unitario_usd', 16, 4);
            $table->decimal('costo_unitario_bs', 16, 4);
            $table->decimal('margen_detal_porcentaje', 8, 2)->default(0);
            $table->decimal('precio_detal_usd', 16, 4);
            $table->decimal('precio_detal_bs', 16, 4)->default(0);
            $table->decimal('margen_mayorista_porcentaje', 8, 2)->default(0);
            $table->decimal('precio_mayorista_usd', 16, 4);
            $table->decimal('precio_mayorista_bs', 16, 4)->default(0);
            $table->decimal('subtotal_usd', 16, 2);
            $table->decimal('subtotal_bs', 16, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recepcion_detalles');
    }
};
