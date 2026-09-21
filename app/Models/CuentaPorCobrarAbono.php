<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaPorCobrarAbono extends Model
{
    use HasFactory;

    protected $table = 'cuentas_por_cobrar_abonos';

    protected $fillable = [
        'cuenta_por_cobrar_id',
        'user_id',
        'metodo_pago_id',
        'fecha_abono',
        'monto_usd',
        'monto_bs',
        'tasa_cambio',
        'referencia',
        'observaciones',
    ];

    protected $casts = [
        'fecha_abono' => 'date',
        'monto_usd' => 'decimal:2',
        'monto_bs' => 'decimal:2',
        'tasa_cambio' => 'decimal:4',
    ];

    public function cuentaPorCobrar(): BelongsTo
    {
        return $this->belongsTo(CuentaPorCobrar::class, 'cuenta_por_cobrar_id');
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
