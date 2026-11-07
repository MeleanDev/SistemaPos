<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Secuencia extends Model
{
    protected $table = 'secuencias';

    protected $fillable = [
        'empresa_id',
        'tipo',
        'ultimo_valor',
    ];

    protected $casts = [
        'empresa_id' => 'integer',
        'ultimo_valor' => 'integer',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Calcula el valor máximo actual existente en la tabla origen cuando se inicializa
     * o cuando aún no existe la fila en `secuencias` (o hay registros manuales mayores).
     */
    public static function maximoEnTablaOrigen(int $empresaId, string $tipo): int
    {
        return match ($tipo) {
            'venta' => (int) (DB::table('ventas')
                ->where('empresa_id', $empresaId)
                ->selectRaw("MAX(CAST(REGEXP_SUBSTR(codigo, '[0-9]+') AS UNSIGNED)) as max_val")
                ->value('max_val') ?? 0),
            'numero_control', 'control' => (int) (DB::table('ventas')
                ->where('empresa_id', $empresaId)
                ->whereNotNull('numero_control')
                ->selectRaw("MAX(CAST(REGEXP_SUBSTR(REGEXP_REPLACE(numero_control, '^00-', ''), '[0-9]+$') AS UNSIGNED)) as max_val")
                ->value('max_val') ?? 0),
            'devolucion' => (int) (DB::table('devoluciones_venta')
                ->where('empresa_id', $empresaId)
                ->selectRaw("MAX(CAST(REGEXP_SUBSTR(codigo, '[0-9]+') AS UNSIGNED)) as max_val")
                ->value('max_val') ?? 0),
            'liquidacion_comision' => (int) (DB::table('liquidaciones_comision')
                ->where('empresa_id', $empresaId)
                ->selectRaw("MAX(CAST(REGEXP_SUBSTR(codigo, '[0-9]+') AS UNSIGNED)) as max_val")
                ->value('max_val') ?? 0),
            default => 0,
        };
    }

    /**
     * Consulta el próximo correlativo sin bloquear ni incrementar la secuencia.
     */
    public static function proximoValorSinIncrementar(int $empresaId, string $tipo): int
    {
        $tipoNormalizado = $tipo === 'control' ? 'numero_control' : $tipo;

        $valorSecuencia = (int) (static::where('empresa_id', $empresaId)
            ->where('tipo', $tipoNormalizado)
            ->value('ultimo_valor') ?? 0);

        $maxOrigen = static::maximoEnTablaOrigen($empresaId, $tipoNormalizado);

        return max($valorSecuencia, $maxOrigen) + 1;
    }

    /**
     * Obtiene e incrementa atómicamente el siguiente valor de la secuencia usando lockForUpdate().
     */
    public static function siguiente(int $empresaId, string $tipo): int
    {
        $tipoNormalizado = $tipo === 'control' ? 'numero_control' : $tipo;

        $ejecutar = function () use ($empresaId, $tipoNormalizado): int {
            $secuencia = static::where('empresa_id', $empresaId)
                ->where('tipo', $tipoNormalizado)
                ->lockForUpdate()
                ->first();

            $maxOrigen = static::maximoEnTablaOrigen($empresaId, $tipoNormalizado);

            if (! $secuencia) {
                $nuevoValor = $maxOrigen + 1;
                static::create([
                    'empresa_id' => $empresaId,
                    'tipo' => $tipoNormalizado,
                    'ultimo_valor' => $nuevoValor,
                ]);

                return $nuevoValor;
            }

            $base = max((int) $secuencia->ultimo_valor, $maxOrigen);
            $nuevoValor = $base + 1;

            $secuencia->ultimo_valor = $nuevoValor;
            $secuencia->save();

            return $nuevoValor;
        };

        if (DB::transactionLevel() > 0) {
            return $ejecutar();
        }

        return DB::transaction($ejecutar);
    }
}
