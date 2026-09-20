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
            $table->decimal('ultimo_margen_detal', 8, 2)->default(30.00)->after('precio_mayorista_bs');
            $table->decimal('ultimo_margen_mayorista', 8, 2)->default(15.00)->after('ultimo_margen_detal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn(['ultimo_margen_detal', 'ultimo_margen_mayorista']);
        });
    }
};
