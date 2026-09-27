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
        if (! Schema::hasTable('modelos_motos')) {
            Schema::create('modelos_motos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->onDelete('cascade');
                $table->string('referencia', 50);
                $table->string('marca', 100);
                $table->string('modelo', 100);
                $table->integer('anio')->default(date('Y'));
                $table->string('color', 100);
                $table->string('cilindrada', 50)->default('150cc');
                $table->text('descripcion')->nullable();
                $table->boolean('estado')->default(true);
                $table->timestamps();

                $table->unique(['empresa_id', 'referencia'], 'uq_modelos_motos_empresa_ref');
                $table->index(['empresa_id', 'estado']);
                $table->index(['empresa_id', 'marca', 'modelo']);
            });
        }

        if (Schema::hasTable('motos') && ! Schema::hasColumn('motos', 'modelo_moto_id')) {
            Schema::table('motos', function (Blueprint $table) {
                $table->foreignId('modelo_moto_id')->nullable()->after('empresa_id')->constrained('modelos_motos')->nullOnDelete();
            });
        }

        if (Schema::hasTable('recepcion_moto_detalles') && ! Schema::hasColumn('recepcion_moto_detalles', 'modelo_moto_id')) {
            Schema::table('recepcion_moto_detalles', function (Blueprint $table) {
                $table->foreignId('modelo_moto_id')->nullable()->after('almacen_id')->constrained('modelos_motos')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('recepcion_moto_detalles') && Schema::hasColumn('recepcion_moto_detalles', 'modelo_moto_id')) {
            Schema::table('recepcion_moto_detalles', function (Blueprint $table) {
                $table->dropForeign(['modelo_moto_id']);
                $table->dropColumn('modelo_moto_id');
            });
        }

        if (Schema::hasTable('motos') && Schema::hasColumn('motos', 'modelo_moto_id')) {
            Schema::table('motos', function (Blueprint $table) {
                $table->dropForeign(['modelo_moto_id']);
                $table->dropColumn('modelo_moto_id');
            });
        }

        Schema::dropIfExists('modelos_motos');
    }
};
