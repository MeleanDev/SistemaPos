<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaMovimiento extends Model
{
    use HasFactory;

    protected $table = 'caja_movimientos';

    protected $fillable = [
        'empresa_id',
        'caja_turno_id',
        'user_id',
        'tipo',
        'monto_usd',
        'monto_bs',
        'concepto',
        'comprobante_referencia',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'monto_usd' => 'decimal:2',
            'monto_bs' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cajaTurno(): BelongsTo
    {
        return $this->belongsTo(CajaTurno::class, 'caja_turno_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
