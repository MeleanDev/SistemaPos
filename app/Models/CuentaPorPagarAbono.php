<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaPorPagarAbono extends Model
{
    use HasFactory;

    protected $table = 'cuentas_por_pagar_abonos';

    protected $fillable = [
        'cuenta_por_pagar_id',
        'user_id',
        'metodo_pago_id',
        'fecha_abono',
        'monto_usd',
        'monto_bs',
        'tasa_cambio',
        'referencia',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_abono' => 'date',
            'monto_usd' => 'decimal:2',
            'monto_bs' => 'decimal:2',
            'tasa_cambio' => 'decimal:4',
        ];
    }

    public function cuentaPorPagar(): BelongsTo
    {
        return $this->belongsTo(CuentaPorPagar::class, 'cuenta_por_pagar_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
}
