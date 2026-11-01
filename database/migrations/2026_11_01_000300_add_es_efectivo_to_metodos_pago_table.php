<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('metodos_pago', 'es_efectivo')) {
            Schema::table('metodos_pago', function (Blueprint $table) {
                $table->boolean('es_efectivo')->default(false)->after('descripcion');
            });

            DB::table('metodos_pago')
                ->whereRaw('LOWER(nombre) LIKE ?', ['%efectivo%'])
                ->orWhereRaw('LOWER(nombre) LIKE ?', ['%cash%'])
                ->update(['es_efectivo' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('metodos_pago', 'es_efectivo')) {
            Schema::table('metodos_pago', function (Blueprint $table) {
                $table->dropColumn('es_efectivo');
            });
        }
    }
};
