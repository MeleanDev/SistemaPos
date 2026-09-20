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
        Schema::create('producto_stock_almacenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->onDelete('cascade');
            $table->foreignId('almacen_id')->constrained('almacenes')->onDelete('cascade');
            $table->decimal('cantidad_actual', 14, 3)->default(0);
            $table->decimal('cantidad_reservada', 14, 3)->default(0);
            $table->string('ubicacion_pasillo', 100)->nullable(); // Ej. Pasillo A, Estante 3
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producto_stock_almacenes');
    }
};
