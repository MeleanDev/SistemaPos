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
            if (! Schema::hasColumn('recepciones', 'tasa_compra')) {
                $table->decimal('tasa_compra', 16, 4)->default(1.0000)->after('tasa_cambio');
            }
            if (! Schema::hasColumn('recepciones', 'tasa_venta')) {
                $table->decimal('tasa_venta', 16, 4)->default(1.0000)->after('tasa_compra');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recepciones', function (Blueprint $table) {
            $table->dropColumn(['tasa_compra', 'tasa_venta']);
        });
    }
};
