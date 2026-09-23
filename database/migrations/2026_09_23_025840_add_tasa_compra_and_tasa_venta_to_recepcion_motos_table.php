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
        Schema::table('recepcion_motos', function (Blueprint $table) {
            $table->decimal('tasa_compra', 12, 4)->nullable()->after('tasa_cambio');
            $table->decimal('tasa_venta', 12, 4)->nullable()->after('tasa_compra');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recepcion_motos', function (Blueprint $table) {
            $table->dropColumn(['tasa_compra', 'tasa_venta']);
        });
    }
};
