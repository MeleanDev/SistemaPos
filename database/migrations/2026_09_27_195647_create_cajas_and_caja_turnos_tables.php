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
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->nullOnDelete();
            $table->string('nombre', 100);
            $table->string('codigo', 50)->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamps();

            $table->unique(['empresa_id', 'nombre']);
            $table->index(['empresa_id', 'estado']);
        });

        Schema::create('caja_turnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('fecha_apertura');
            $table->time('hora_apertura');
            $table->decimal('monto_apertura_usd', 14, 2)->default(0.00);
            $table->decimal('monto_apertura_bs', 14, 2)->default(0.00);
            $table->date('fecha_cierre')->nullable();
            $table->time('hora_cierre')->nullable();
            $table->decimal('monto_cierre_usd', 14, 2)->nullable();
            $table->decimal('monto_cierre_bs', 14, 2)->nullable();
            $table->decimal('total_ventas_usd', 14, 2)->default(0.00);
            $table->decimal('total_ventas_bs', 14, 2)->default(0.00);
            $table->decimal('total_devoluciones_usd', 14, 2)->default(0.00);
            $table->decimal('total_devoluciones_bs', 14, 2)->default(0.00);
            $table->decimal('diferencia_usd', 14, 2)->nullable();
            $table->decimal('diferencia_bs', 14, 2)->nullable();
            $table->enum('estado', ['abierta', 'cerrada'])->default('abierta');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['empresa_id', 'estado']);
            $table->index(['caja_id', 'estado']);
            $table->index(['user_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_turnos');
        Schema::dropIfExists('cajas');
    }
};
