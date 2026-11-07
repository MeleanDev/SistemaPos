<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cuentas_por_cobrar') && Schema::hasColumn('cuentas_por_cobrar', 'estado')) {
            Schema::table('cuentas_por_cobrar', function (Blueprint $table) {
                $table->string('estado', 20)->default('pendiente')->change();
            });
        }
    }

    public function down(): void
    {
        // Reversible de forma segura sin truncar estados 'anulada' existentes
    }
};
