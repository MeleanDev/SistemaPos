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
        Schema::table('recepcion_detalles', function (Blueprint $table) {
            if (! Schema::hasColumn('recepcion_detalles', 'variante_texto')) {
                $table->string('variante_texto', 150)->nullable()->after('almacen_id');
            }
            if (! Schema::hasColumn('recepcion_detalles', 'seriales_ingresados')) {
                $table->json('seriales_ingresados')->nullable()->after('variante_texto');
            }
        });

        Schema::table('venta_detalles', function (Blueprint $table) {
            if (! Schema::hasColumn('venta_detalles', 'variante_texto')) {
                $table->string('variante_texto', 150)->nullable()->after('nombre_item');
            }
            if (! Schema::hasColumn('venta_detalles', 'producto_serial_id')) {
                $table->foreignId('producto_serial_id')->nullable()->after('serial_identificador')->constrained('producto_seriales')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recepcion_detalles', function (Blueprint $table) {
            $table->dropColumn(['variante_texto', 'seriales_ingresados']);
        });

        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropForeign(['producto_serial_id']);
            $table->dropColumn(['variante_texto', 'producto_serial_id']);
        });
    }
};
