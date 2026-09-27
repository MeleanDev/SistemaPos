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
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('codigo', 50); // TRASF-00001, AJUST-00001
            $table->string('tipo', 50); // traslado, ajuste_entrada, ajuste_salida
            $table->foreignId('almacen_origen_id')->nullable()->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('almacen_destino_id')->nullable()->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('motivo', 255);
            $table->text('observaciones')->nullable();
            $table->date('fecha');
            $table->integer('total_items')->default(1);
            $table->decimal('total_unidades', 14, 3)->default(0);
            $table->timestamps();

            $table->index(['empresa_id', 'tipo']);
            $table->index(['empresa_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
