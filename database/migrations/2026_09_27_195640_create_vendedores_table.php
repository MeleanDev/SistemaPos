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
        Schema::create('vendedores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('tipo_documento', 2)->default('V');
            $table->string('documento', 25);
            $table->string('nombre', 150);
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 150)->nullable();
            $table->decimal('comision_porcentaje', 5, 2)->default(0.00);
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'documento']);
            $table->index(['empresa_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendedores');
    }
};
