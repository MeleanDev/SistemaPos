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
        Schema::create('recepciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('codigo', 50); // Correlativo ej. REC-00001
            $table->enum('tipo_documento', ['factura', 'nota_entrega', 'guia_despacho', 'orden_compra'])->default('factura');
            $table->string('numero_documento', 100); // N° físico del proveedor
            $table->date('fecha_emision');
            $table->date('fecha_recepcion');
            $table->enum('condicion_pago', ['contado', 'credito'])->default('contado');
            $table->unsignedInteger('dias_credito')->default(0);
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('tasa_cambio', 16, 4)->default(1.0000);
            $table->decimal('subtotal_usd', 16, 2)->default(0);
            $table->decimal('iva_usd', 16, 2)->default(0);
            $table->decimal('total_usd', 16, 2)->default(0);
            $table->decimal('total_bs', 16, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['procesada', 'anulada'])->default('procesada');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recepciones');
    }
};
