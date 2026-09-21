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
        Schema::create('ventas_en_espera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->enum('tipo_venta', ['detal', 'mayor'])->default('detal');
            $table->string('nota_referencia', 100)->nullable();
            $table->json('datos_json');
            $table->decimal('total_usd', 16, 2)->default(0);
            $table->decimal('total_bs', 16, 2)->default(0);
            $table->timestamps();

            $table->index(['empresa_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas_en_espera');
    }
};
