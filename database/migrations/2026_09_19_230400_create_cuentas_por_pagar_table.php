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
        Schema::create('cuentas_por_pagar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('restrict');
            $table->foreignId('recepcion_id')->nullable()->constrained('recepciones')->onDelete('cascade');
            $table->string('numero_factura', 100);
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->decimal('monto_total_usd', 16, 2);
            $table->decimal('monto_total_bs', 16, 2);
            $table->decimal('monto_pagado_usd', 16, 2)->default(0);
            $table->decimal('monto_pagado_bs', 16, 2)->default(0);
            $table->decimal('saldo_pendiente_usd', 16, 2);
            $table->decimal('saldo_pendiente_bs', 16, 2);
            $table->enum('estado', ['pendiente', 'parcial', 'pagada', 'anulada'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_pagar');
    }
};
