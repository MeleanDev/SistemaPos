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
        if (! Schema::hasTable('caja_movimientos')) {
            Schema::create('caja_movimientos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->foreignId('caja_turno_id')->constrained('caja_turnos')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->enum('tipo', ['ingreso', 'egreso'])->default('egreso');
                $table->decimal('monto_usd', 14, 2)->default(0.00);
                $table->decimal('monto_bs', 14, 2)->default(0.00);
                $table->string('concepto', 255);
                $table->string('comprobante_referencia', 100)->nullable();
                $table->text('observaciones')->nullable();
                $table->timestamps();

                $table->index(['empresa_id', 'caja_turno_id']);
                $table->index(['caja_turno_id', 'tipo']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caja_movimientos');
    }
};
