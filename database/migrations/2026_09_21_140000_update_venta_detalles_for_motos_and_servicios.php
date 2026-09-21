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
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->change();
            $table->foreignId('moto_id')->nullable()->after('producto_id')->constrained('motos')->nullOnDelete();
            $table->foreignId('servicio_id')->nullable()->after('moto_id')->constrained('servicios')->nullOnDelete();
            $table->string('nombre_item', 255)->nullable()->after('tipo_item');
            $table->string('serial_identificador', 100)->nullable()->after('nombre_item');
        });

        Schema::table('devolucion_venta_detalles', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->change();
            $table->foreignId('moto_id')->nullable()->after('producto_id')->constrained('motos')->nullOnDelete();
            $table->foreignId('servicio_id')->nullable()->after('moto_id')->constrained('servicios')->nullOnDelete();
            $table->string('nombre_item', 255)->nullable()->after('almacen_id');
            $table->string('serial_identificador', 100)->nullable()->after('nombre_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moto_id');
            $table->dropConstrainedForeignId('servicio_id');
            $table->dropColumn(['nombre_item', 'serial_identificador']);
        });

        Schema::table('devolucion_venta_detalles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moto_id');
            $table->dropConstrainedForeignId('servicio_id');
            $table->dropColumn(['nombre_item', 'serial_identificador']);
        });
    }
};
