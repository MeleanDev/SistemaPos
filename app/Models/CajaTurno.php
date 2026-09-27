<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CajaTurno extends Model
{
    use HasFactory;

    protected $table = 'caja_turnos';

    protected $fillable = [
        'empresa_id',
        'caja_id',
        'user_id',
        'aperturado_por_id',
        'cerrado_por_id',
        'fecha_apertura',
        'hora_apertura',
        'monto_apertura_usd',
        'monto_apertura_bs',
        'fecha_cierre',
        'hora_cierre',
        'monto_cierre_usd',
        'monto_cierre_bs',
        'total_ventas_usd',
        'total_ventas_bs',
        'total_devoluciones_usd',
        'total_devoluciones_bs',
        'diferencia_usd',
        'diferencia_bs',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_apertura' => 'date',
            'fecha_cierre' => 'date',
            'monto_apertura_usd' => 'decimal:2',
            'monto_apertura_bs' => 'decimal:2',
            'monto_cierre_usd' => 'decimal:2',
            'monto_cierre_bs' => 'decimal:2',
            'total_ventas_usd' => 'decimal:2',
            'total_ventas_bs' => 'decimal:2',
            'total_devoluciones_usd' => 'decimal:2',
            'total_devoluciones_bs' => 'decimal:2',
            'diferencia_usd' => 'decimal:2',
            'diferencia_bs' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function aperturadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aperturado_por_id');
    }

    public function cerradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cerrado_por_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'caja_turno_id');
    }

    public function devoluciones(): HasMany
    {
        return $this->hasMany(DevolucionVenta::class, 'caja_turno_id');
    }
}
