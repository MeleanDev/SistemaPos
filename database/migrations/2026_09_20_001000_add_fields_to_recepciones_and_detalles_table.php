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
            $table->string('numero_control', 100)->nullable()->after('numero_documento');
            $table->string('moneda_documento', 10)->default('USD')->after('tasa_cambio'); // 'USD' o 'VES'
            $table->decimal('monto_bruto_usd', 16, 2)->default(0)->after('moneda_documento');
            $table->decimal('monto_bruto_bs', 16, 2)->default(0)->after('monto_bruto_usd');
            $table->decimal('descuento_global_porcentaje', 8, 2)->default(0)->after('monto_bruto_bs');
            $table->decimal('descuento_global_usd', 16, 2)->default(0)->after('descuento_global_porcentaje');
            $table->decimal('descuento_global_bs', 16, 2)->default(0)->after('descuento_global_usd');
        });

        Schema::table('recepcion_detalles', function (Blueprint $table) {
            $table->foreignId('almacen_id')->nullable()->after('producto_id')->constrained('almacenes')->onDelete('restrict');
            $table->decimal('bultos', 14, 3)->nullable()->after('cantidad');
            $table->decimal('unidades_por_bulto', 14, 3)->nullable()->after('bultos');
            $table->decimal('costo_bulto_usd', 16, 4)->default(0)->after('unidades_por_bulto');
            $table->decimal('costo_bulto_bs', 16, 4)->default(0)->after('costo_bulto_usd');
            $table->decimal('descuento_porcentaje', 8, 2)->default(0)->after('costo_unitario_bs');
            $table->decimal('descuento_usd', 16, 4)->default(0)->after('descuento_porcentaje');
            $table->decimal('descuento_bs', 16, 4)->default(0)->after('descuento_usd');
            $table->boolean('aplica_iva')->default(true)->after('descuento_bs');
            $table->decimal('iva_porcentaje', 8, 2)->default(16.00)->after('aplica_iva');
            $table->decimal('iva_monto_usd', 16, 4)->default(0)->after('iva_porcentaje');
            $table->decimal('iva_monto_bs', 16, 4)->default(0)->after('iva_monto_usd');
            $table->decimal('costo_anterior_bs', 16, 4)->default(0)->after('costo_anterior_usd');
            $table->decimal('precio_detal_anterior_usd', 16, 4)->default(0)->after('costo_anterior_bs');
            $table->decimal('precio_detal_anterior_bs', 16, 4)->default(0)->after('precio_detal_anterior_usd');
            $table->decimal('precio_mayorista_anterior_usd', 16, 4)->default(0)->after('precio_detal_anterior_bs');
            $table->decimal('precio_mayorista_anterior_bs', 16, 4)->default(0)->after('precio_mayorista_anterior_usd');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recepciones', function (Blueprint $table) {
            $table->dropColumn([
                'numero_control',
                'moneda_documento',
                'monto_bruto_usd',
                'monto_bruto_bs',
                'descuento_global_porcentaje',
                'descuento_global_usd',
                'descuento_global_bs',
            ]);
        });

        Schema::table('recepcion_detalles', function (Blueprint $table) {
            $table->dropForeign(['almacen_id']);
            $table->dropColumn([
                'almacen_id',
                'bultos',
                'unidades_por_bulto',
                'costo_bulto_usd',
                'costo_bulto_bs',
                'descuento_porcentaje',
                'descuento_usd',
                'descuento_bs',
                'aplica_iva',
                'iva_porcentaje',
                'iva_monto_usd',
                'iva_monto_bs',
                'costo_anterior_bs',
                'precio_detal_anterior_usd',
                'precio_detal_anterior_bs',
                'precio_mayorista_anterior_usd',
                'precio_mayorista_anterior_bs',
            ]);
        });
    }
};
