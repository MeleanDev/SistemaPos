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
        if (Schema::hasTable('motos') && ! Schema::hasColumn('motos', 'iva_porcentaje')) {
            Schema::table('motos', function (Blueprint $table) {
                $table->decimal('iva_porcentaje', 8, 2)->default(16.00)->after('flete_bs');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('motos') && Schema::hasColumn('motos', 'iva_porcentaje')) {
            Schema::table('motos', function (Blueprint $table) {
                $table->dropColumn('iva_porcentaje');
            });
        }
    }
};
