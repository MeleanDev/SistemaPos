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
        Schema::create('empresa_monedas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->string('codigo', 10); // USD, EUR, COP, etc.
            $table->string('nombre', 100);
            $table->string('simbolo', 10)->default('$');
            $table->decimal('tasa_cambio', 16, 4)->default(1.0000); // Valor en moneda principal (VES Bs.)
            $table->boolean('es_principal')->default(false);
            $table->boolean('estado')->default(true);
            $table->timestamp('ultima_actualizacion_tasa')->nullable();
            $table->timestamps();

            $table->unique(['empresa_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresa_monedas');
    }
};
