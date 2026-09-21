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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('codigo', 50); // VEN-00001
            $table->string('numero_control', 50)->nullable();
            $table->enum('tipo_venta', ['detal', 'mayor'])->default('detal');
            $table->string('moneda', 10)->default('USD');
            $table->decimal('tasa_cambio', 16, 4)->default(1.0000);
            $table->date('fecha_emision');
            $table->time('hora_emision');
            $table->decimal('monto_bruto_usd', 16, 2)->default(0);
            $table->decimal('monto_bruto_bs', 16, 2)->default(0);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->decimal('descuento_usd', 16, 2)->default(0);
            $table->decimal('descuento_bs', 16, 2)->default(0);
            $table->decimal('subtotal_neto_usd', 16, 2)->default(0);
            $table->decimal('subtotal_neto_bs', 16, 2)->default(0);
            $table->decimal('iva_monto_usd', 16, 2)->default(0);
            $table->decimal('iva_monto_bs', 16, 2)->default(0);
            $table->decimal('igtf_porcentaje', 5, 2)->default(0);
            $table->decimal('igtf_monto_usd', 16, 2)->default(0);
            $table->decimal('igtf_monto_bs', 16, 2)->default(0);
            $table->decimal('total_usd', 16, 2)->default(0);
            $table->decimal('total_bs', 16, 2)->default(0);
            $table->enum('condicion_pago', ['contado', 'credito', 'mixto'])->default('contado');
            $table->decimal('monto_pagado_usd', 16, 2)->default(0);
            $table->decimal('monto_pagado_bs', 16, 2)->default(0);
            $table->decimal('vuelto_usd', 16, 2)->default(0);
            $table->decimal('vuelto_bs', 16, 2)->default(0);
            $table->decimal('saldo_pendiente_usd', 16, 2)->default(0);
            $table->decimal('saldo_pendiente_bs', 16, 2)->default(0);
            $table->enum('estado', ['completada', 'anulada', 'devuelta_parcial', 'devuelta_total'])->default('completada');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'fecha_emision']);
            $table->index(['empresa_id', 'cliente_id']);
            $table->index(['empresa_id', 'codigo']);
        });

        Schema::create('venta_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');
            $table->foreignId('producto_id')->constrained('productos')->onDelete('restrict');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('restrict');
            $table->string('tipo_item', 30)->default('producto'); // producto, servicio, moto
            $table->decimal('cantidad', 14, 3);
            $table->decimal('costo_unitario_usd', 16, 4)->default(0);
            $table->decimal('costo_unitario_bs', 16, 4)->default(0);
            $table->decimal('precio_unitario_usd', 16, 4);
            $table->decimal('precio_unitario_bs', 16, 4);
            $table->decimal('descuento_porcentaje', 5, 2)->default(0);
            $table->decimal('descuento_usd', 16, 2)->default(0);
            $table->decimal('descuento_bs', 16, 2)->default(0);
            $table->boolean('aplica_iva')->default(true);
            $table->decimal('iva_porcentaje', 5, 2)->default(16.00);
            $table->decimal('iva_monto_usd', 16, 2)->default(0);
            $table->decimal('iva_monto_bs', 16, 2)->default(0);
            $table->decimal('subtotal_usd', 16, 2);
            $table->decimal('subtotal_bs', 16, 2);
            $table->timestamps();

            $table->index(['venta_id', 'producto_id']);
        });

        Schema::create('venta_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago')->onDelete('restrict');
            $table->string('moneda', 10)->default('USD'); // USD, VES, COP, EUR
            $table->decimal('tasa_cambio', 16, 4)->default(1.0000);
            $table->decimal('monto_origen', 16, 2);
            $table->decimal('monto_usd', 16, 2);
            $table->decimal('monto_bs', 16, 2);
            $table->string('referencia', 100)->nullable();
            $table->timestamps();

            $table->index(['venta_id', 'metodo_pago_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venta_pagos');
        Schema::dropIfExists('venta_detalles');
        Schema::dropIfExists('ventas');
    }
};
