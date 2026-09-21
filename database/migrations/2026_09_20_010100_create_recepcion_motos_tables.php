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
        Schema::create('recepcion_motos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('codigo', 50); // RECMOTO-00001
            $table->enum('tipo_documento', ['factura', 'nota_entrega', 'guia_despacho', 'orden_compra'])->default('factura');
            $table->string('numero_documento', 100);
            $table->string('numero_control', 100)->nullable();
            $table->string('moneda_documento', 10)->default('USD');
            $table->decimal('tasa_cambio', 16, 4)->default(1.0000);
            $table->date('fecha_emision');
            $table->date('fecha_recepcion');
            $table->enum('condicion_pago', ['contado', 'credito'])->default('contado');
            $table->unsignedInteger('dias_credito')->default(0);
            $table->date('fecha_vencimiento')->nullable();
            $table->decimal('monto_bruto_usd', 16, 2)->default(0);
            $table->decimal('monto_bruto_bs', 16, 2)->default(0);
            $table->decimal('descuento_global_porcentaje', 8, 2)->default(0);
            $table->decimal('descuento_global_usd', 16, 2)->default(0);
            $table->decimal('descuento_global_bs', 16, 2)->default(0);
            $table->decimal('subtotal_usd', 16, 2)->default(0);
            $table->decimal('subtotal_bs', 16, 2)->default(0);
            $table->decimal('iva_usd', 16, 2)->default(0);
            $table->decimal('iva_bs', 16, 2)->default(0);
            $table->decimal('total_usd', 16, 2)->default(0);
            $table->decimal('total_bs', 16, 2)->default(0);
            $table->unsignedInteger('total_unidades')->default(0);
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['procesada', 'anulada'])->default('procesada');
            $table->timestamps();
        });

        Schema::create('recepcion_moto_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recepcion_moto_id')->constrained('recepcion_motos')->onDelete('cascade');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('restrict');
            $table->string('referencia', 100);
            $table->string('marca', 100);
            $table->string('modelo', 100);
            $table->string('anio', 10);
            $table->string('color', 100);
            $table->string('cilindrada', 50);
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('costo_unitario_usd', 16, 4)->default(0);
            $table->decimal('costo_unitario_bs', 16, 4)->default(0);
            $table->decimal('descuento_porcentaje', 8, 2)->default(0);
            $table->decimal('descuento_usd', 16, 4)->default(0);
            $table->decimal('descuento_bs', 16, 4)->default(0);
            $table->boolean('aplica_iva')->default(true);
            $table->decimal('iva_porcentaje', 8, 2)->default(16.00);
            $table->decimal('iva_monto_usd', 16, 4)->default(0);
            $table->decimal('iva_monto_bs', 16, 4)->default(0);
            $table->decimal('margen_detal', 8, 2)->default(0);
            $table->decimal('precio_detal_usd', 16, 4)->default(0);
            $table->decimal('precio_detal_bs', 16, 4)->default(0);
            $table->decimal('margen_mayorista', 8, 2)->default(0);
            $table->decimal('precio_mayorista_usd', 16, 4)->default(0);
            $table->decimal('precio_mayorista_bs', 16, 4)->default(0);
            $table->decimal('subtotal_usd', 16, 2)->default(0);
            $table->decimal('subtotal_bs', 16, 2)->default(0);
            $table->decimal('total_usd', 16, 2)->default(0);
            $table->decimal('total_bs', 16, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recepcion_moto_detalles');
        Schema::dropIfExists('recepcion_motos');
    }
};
