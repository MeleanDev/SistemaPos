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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('categoria_id')->constrained('categorias')->onDelete('restrict');
            $table->enum('tipo', ['producto', 'servicio'])->default('producto');
            $table->string('codigo_interno', 50); // SKU / Código único por empresa
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->string('unidad_medida', 30)->default('unidad'); // unidad, kilo, gramo, litro, bulto, caja, paquete, metro

            // Control de Inventario
            $table->decimal('stock_minimo', 12, 2)->default(0);
            $table->decimal('stock_maximo', 12, 2)->nullable();

            // Precios Multi-Moneda (Moneda Secundaria USD y Moneda Principal Bs.)
            $table->decimal('precio_costo_usd', 16, 4)->default(0);
            $table->decimal('precio_costo_bs', 16, 4)->default(0);
            $table->decimal('precio_detal_usd', 16, 4)->default(0);
            $table->decimal('precio_detal_bs', 16, 4)->default(0);
            $table->decimal('precio_mayorista_usd', 16, 4)->default(0);
            $table->decimal('precio_mayorista_bs', 16, 4)->default(0);
            $table->decimal('tasa_cambio', 16, 4)->default(1);

            // Régimen Fiscal (IVA e IGTF)
            $table->boolean('aplica_iva')->default(true);
            $table->decimal('iva_porcentaje', 5, 2)->default(16.00);
            $table->boolean('aplica_igtf')->default(true);
            $table->decimal('igtf_porcentaje', 5, 2)->default(3.00);

            $table->string('imagen', 255)->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
