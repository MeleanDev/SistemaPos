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
        Schema::create('motos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('proveedor_id')->constrained('proveedores')->onDelete('restrict');
            $table->foreignId('recepcion_moto_id')->nullable()->constrained('recepcion_motos')->nullOnDelete();
            $table->foreignId('recepcion_moto_detalle_id')->nullable()->constrained('recepcion_moto_detalles')->nullOnDelete();
            $table->string('referencia', 100);
            $table->string('marca', 100);
            $table->string('modelo', 100);
            $table->string('anio', 10);
            $table->string('color', 100);
            $table->string('cilindrada', 50);
            $table->string('numero_niv', 50);
            $table->string('numero_chasis', 100);
            $table->string('numero_motor', 100);
            $table->string('certificado_origen', 100);
            $table->string('placa', 50)->nullable();
            $table->decimal('precio_costo_usd', 16, 4)->default(0);
            $table->decimal('precio_costo_bs', 16, 4)->default(0);
            $table->decimal('margen_detal', 8, 2)->default(0);
            $table->decimal('precio_detal_usd', 16, 4)->default(0);
            $table->decimal('precio_detal_bs', 16, 4)->default(0);
            $table->decimal('margen_mayorista', 8, 2)->default(0);
            $table->decimal('precio_mayorista_usd', 16, 4)->default(0);
            $table->decimal('precio_mayorista_bs', 16, 4)->default(0);
            $table->enum('estado', ['disponible', 'reservada', 'vendida', 'en_mantenimiento', 'anulada'])->default('disponible');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'numero_niv']);
            $table->index(['empresa_id', 'numero_chasis']);
            $table->index(['empresa_id', 'numero_motor']);
            $table->index(['empresa_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('motos');
    }
};
