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
        Schema::create('cuentas_por_pagar_abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuenta_por_pagar_id')->constrained('cuentas_por_pagar')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('metodo_pago_id')->constrained('metodos_pago')->onDelete('restrict');
            $table->date('fecha_abono');
            $table->decimal('monto_usd', 16, 2);
            $table->decimal('monto_bs', 16, 2);
            $table->decimal('tasa_cambio', 16, 4);
            $table->string('referencia', 100)->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['cuenta_por_pagar_id', 'fecha_abono']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cuentas_por_pagar_abonos');
    }
};
