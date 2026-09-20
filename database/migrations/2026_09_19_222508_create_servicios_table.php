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
        Schema::create('servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('restrict');
            $table->string('codigo', 50); // Código / SKU único por empresa
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();

            // Precios Multi-Moneda
            $table->decimal('precio_costo_usd', 16, 4)->default(0);
            $table->decimal('precio_costo_bs', 16, 4)->default(0);
            $table->decimal('precio_venta_usd', 16, 4)->default(0);
            $table->decimal('precio_venta_bs', 16, 4)->default(0);

            // Régimen Fiscal (IVA e IGTF)
            $table->boolean('aplica_iva')->default(true);
            $table->decimal('iva_porcentaje', 5, 2)->default(16.00);
            $table->boolean('aplica_igtf')->default(true);
            $table->decimal('igtf_porcentaje', 5, 2)->default(3.00);

            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servicios');
    }
};
