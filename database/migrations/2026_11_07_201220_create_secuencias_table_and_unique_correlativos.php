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
        if (! Schema::hasTable('secuencias')) {
            Schema::create('secuencias', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->string('tipo', 30);
                $table->unsignedBigInteger('ultimo_valor')->default(0);
                $table->timestamps();

                $table->unique(['empresa_id', 'tipo']);
            });
        }

        $ahora = now();

        // Inicializar secuencias de 'venta' y 'numero_control' desde ventas existentes
        $ventas = DB::table('ventas')->select('empresa_id', 'codigo', 'numero_control')->get();
        $maxVentasPorEmpresa = [];
        $maxControlPorEmpresa = [];

        foreach ($ventas as $v) {
            $empId = (int) $v->empresa_id;

            $cod = trim((string) ($v->codigo ?? ''));
            if ($cod !== '' && preg_match('/(\d+)/', $cod, $mCod)) {
                $valCod = (int) $mCod[1];
                $maxVentasPorEmpresa[$empId] = max($maxVentasPorEmpresa[$empId] ?? 0, $valCod);
            }

            $ctrl = trim((string) ($v->numero_control ?? ''));
            if ($ctrl !== '') {
                $sinPrefijo = preg_replace('/^00-/', '', $ctrl);
                if (preg_match('/(\d+)$/', (string) $sinPrefijo, $mCtrl)) {
                    $valCtrl = (int) $mCtrl[1];
                    $maxControlPorEmpresa[$empId] = max($maxControlPorEmpresa[$empId] ?? 0, $valCtrl);
                }
            }
        }

        foreach ($maxVentasPorEmpresa as $empId => $maxVal) {
            DB::table('secuencias')->updateOrInsert(
                ['empresa_id' => $empId, 'tipo' => 'venta'],
                ['ultimo_valor' => $maxVal, 'created_at' => $ahora, 'updated_at' => $ahora]
            );
        }

        foreach ($maxControlPorEmpresa as $empId => $maxVal) {
            DB::table('secuencias')->updateOrInsert(
                ['empresa_id' => $empId, 'tipo' => 'numero_control'],
                ['ultimo_valor' => $maxVal, 'created_at' => $ahora, 'updated_at' => $ahora]
            );
        }

        // Inicializar secuencias de 'devolucion' desde devoluciones_venta existentes
        $devoluciones = DB::table('devoluciones_venta')->select('empresa_id', 'codigo')->get();
        $maxDevPorEmpresa = [];

        foreach ($devoluciones as $d) {
            $empId = (int) $d->empresa_id;
            $cod = trim((string) ($d->codigo ?? ''));
            if ($cod !== '' && preg_match('/(\d+)/', $cod, $mDev)) {
                $valDev = (int) $mDev[1];
                $maxDevPorEmpresa[$empId] = max($maxDevPorEmpresa[$empId] ?? 0, $valDev);
            }
        }

        foreach ($maxDevPorEmpresa as $empId => $maxVal) {
            DB::table('secuencias')->updateOrInsert(
                ['empresa_id' => $empId, 'tipo' => 'devolucion'],
                ['ultimo_valor' => $maxVal, 'created_at' => $ahora, 'updated_at' => $ahora]
            );
        }

        // Agregar índices únicos en ventas (si no existen duplicados)
        $dupVentasCodigo = DB::table('ventas')
            ->select('empresa_id', 'codigo')
            ->groupBy('empresa_id', 'codigo')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        $dupVentasControl = DB::table('ventas')
            ->whereNotNull('numero_control')
            ->select('empresa_id', 'numero_control')
            ->groupBy('empresa_id', 'numero_control')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        Schema::table('ventas', function (Blueprint $table) use ($dupVentasCodigo, $dupVentasControl) {
            if (! $dupVentasCodigo) {
                $table->unique(['empresa_id', 'codigo'], 'ventas_empresa_id_codigo_unique');
            }
            if (! $dupVentasControl) {
                $table->unique(['empresa_id', 'numero_control'], 'ventas_empresa_id_numero_control_unique');
            }
        });

        // Agregar índice único en devoluciones_venta (si no existen duplicados)
        $dupDevCodigo = DB::table('devoluciones_venta')
            ->select('empresa_id', 'codigo')
            ->groupBy('empresa_id', 'codigo')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $dupDevCodigo) {
            Schema::table('devoluciones_venta', function (Blueprint $table) {
                $table->unique(['empresa_id', 'codigo'], 'devoluciones_venta_empresa_id_codigo_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devoluciones_venta', function (Blueprint $table) {
            $table->dropUnique('devoluciones_venta_empresa_id_codigo_unique');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->dropUnique('ventas_empresa_id_codigo_unique');
            $table->dropUnique('ventas_empresa_id_numero_control_unique');
        });

        Schema::dropIfExists('secuencias');
    }
};
