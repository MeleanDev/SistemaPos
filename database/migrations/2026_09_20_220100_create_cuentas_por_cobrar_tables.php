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
        Schema::create('cuentas_por_cobrar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->onDelete('cascade');
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

            $table->index(['empresa_id', 'cliente_id']);
            $table->index(['empresa_id', 'estado']);
        });

        Schema::create('cuentas_por_cobrar_abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_por_cobrar_id')->constrained('cuentas_por_cobrar')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago')->onDelete('restrict');
            $table->date('fecha_abono');
            $table->decimal('monto_usd', 16, 2);
            $table->decimal('monto_bs', 16, 2);
            $table->decimal('tasa_cambio', 16, 4);
            $table->string('referencia', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['cuenta_por_cobrar_id', 'fecha_abono']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_cobrar_abonos');
        Schema::dropIfExists('cuentas_por_cobrar');
    }
};
