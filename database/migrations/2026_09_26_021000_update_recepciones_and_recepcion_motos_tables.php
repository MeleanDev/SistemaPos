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
        Schema::table('recepciones', function (Blueprint $table) {
            $table->decimal('base_imponible_usd', 16, 2)->default(0)->after('monto_bruto_bs');
            $table->decimal('base_imponible_bs', 16, 2)->default(0)->after('base_imponible_usd');
            $table->decimal('exento_usd', 16, 2)->default(0)->after('base_imponible_bs');
            $table->decimal('exento_bs', 16, 2)->default(0)->after('exento_usd');
            $table->decimal('iva_porcentaje', 8, 2)->default(16.00)->after('subtotal_usd');
            $table->decimal('iva_bs', 16, 2)->default(0)->after('iva_usd');
        });

        Schema::table('recepcion_motos', function (Blueprint $table) {
            $table->decimal('base_imponible_usd', 16, 2)->default(0)->after('monto_bruto_bs');
            $table->decimal('base_imponible_bs', 16, 2)->default(0)->after('base_imponible_usd');
            $table->decimal('exento_usd', 16, 2)->default(0)->after('base_imponible_bs');
            $table->decimal('exento_bs', 16, 2)->default(0)->after('exento_usd');
            $table->decimal('iva_porcentaje', 8, 2)->default(16.00)->after('subtotal_bs');
            $table->decimal('flete_total_usd', 16, 2)->default(0)->after('iva_bs');
            $table->decimal('flete_total_bs', 16, 2)->default(0)->after('flete_total_usd');
            $table->boolean('incluir_flete_en_factura')->default(false)->after('flete_total_bs');
        });

        Schema::table('recepcion_moto_detalles', function (Blueprint $table) {
            $table->enum('tipo_item', ['moto', 'producto'])->default('moto')->after('almacen_id');
            $table->foreignId('producto_id')->nullable()->after('tipo_item')->constrained('productos')->nullOnDelete();

            $table->string('marca', 100)->nullable()->change();
            $table->string('modelo', 100)->nullable()->change();
            $table->string('anio', 10)->nullable()->change();
            $table->string('color', 100)->nullable()->change();
            $table->string('cilindrada', 50)->nullable()->change();

            $table->decimal('flete_unitario_usd', 16, 4)->default(0)->after('costo_unitario_bs');
            $table->decimal('flete_unitario_bs', 16, 4)->default(0)->after('flete_unitario_usd');
            $table->decimal('costo_total_unitario_usd', 16, 4)->default(0)->after('flete_unitario_bs');
            $table->decimal('costo_total_unitario_bs', 16, 4)->default(0)->after('costo_total_unitario_usd');
            $table->decimal('precio_detal_con_iva_usd', 16, 4)->default(0)->after('precio_detal_bs');
            $table->decimal('precio_detal_con_iva_bs', 16, 4)->default(0)->after('precio_detal_con_iva_usd');
            $table->decimal('precio_mayorista_con_iva_usd', 16, 4)->default(0)->after('precio_mayorista_bs');
            $table->decimal('precio_mayorista_con_iva_bs', 16, 4)->default(0)->after('precio_mayorista_con_iva_usd');
        });

        Schema::table('motos', function (Blueprint $table) {
            $table->decimal('costo_base_usd', 16, 4)->default(0)->after('placa');
            $table->decimal('costo_base_bs', 16, 4)->default(0)->after('costo_base_usd');
            $table->decimal('flete_usd', 16, 4)->default(0)->after('costo_base_bs');
            $table->decimal('flete_bs', 16, 4)->default(0)->after('flete_usd');
            $table->decimal('precio_detal_con_iva_usd', 16, 4)->default(0)->after('precio_detal_bs');
            $table->decimal('precio_detal_con_iva_bs', 16, 4)->default(0)->after('precio_detal_con_iva_usd');
            $table->decimal('precio_mayorista_con_iva_usd', 16, 4)->default(0)->after('precio_mayorista_bs');
            $table->decimal('precio_mayorista_con_iva_bs', 16, 4)->default(0)->after('precio_mayorista_con_iva_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('motos', function (Blueprint $table) {
            $table->dropColumn([
                'costo_base_usd',
                'costo_base_bs',
                'flete_usd',
                'flete_bs',
                'precio_detal_con_iva_usd',
                'precio_detal_con_iva_bs',
                'precio_mayorista_con_iva_usd',
                'precio_mayorista_con_iva_bs',
            ]);
        });

        Schema::table('recepcion_moto_detalles', function (Blueprint $table) {
            $table->dropForeign(['producto_id']);
            $table->dropColumn([
                'tipo_item',
                'producto_id',
                'flete_unitario_usd',
                'flete_unitario_bs',
                'costo_total_unitario_usd',
                'costo_total_unitario_bs',
                'precio_detal_con_iva_usd',
                'precio_detal_con_iva_bs',
                'precio_mayorista_con_iva_usd',
                'precio_mayorista_con_iva_bs',
            ]);
        });

        Schema::table('recepcion_motos', function (Blueprint $table) {
            $table->dropColumn([
                'base_imponible_usd',
                'base_imponible_bs',
                'exento_usd',
                'exento_bs',
                'iva_porcentaje',
                'flete_total_usd',
                'flete_total_bs',
                'incluir_flete_en_factura',
            ]);
        });

        Schema::table('recepciones', function (Blueprint $table) {
            $table->dropColumn([
                'base_imponible_usd',
                'base_imponible_bs',
                'exento_usd',
                'exento_bs',
                'iva_porcentaje',
                'iva_bs',
            ]);
        });
    }
};
