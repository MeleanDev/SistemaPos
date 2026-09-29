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
        Schema::table('productos', function (Blueprint $table) {
            if (! Schema::hasColumn('productos', 'maneja_variantes')) {
                $table->boolean('maneja_variantes')->default(false)->after('unidad_medida');
            }
            if (! Schema::hasColumn('productos', 'atributos_variantes')) {
                $table->json('atributos_variantes')->nullable()->after('maneja_variantes');
            }
            if (! Schema::hasColumn('productos', 'maneja_seriales')) {
                $table->boolean('maneja_seriales')->default(false)->after('atributos_variantes');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['maneja_variantes', 'atributos_variantes', 'maneja_seriales']);
        });
    }
};
