<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaPorCobrar extends Model
{
    use HasFactory;

    protected $table = 'cuentas_por_cobrar';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'venta_id',
        'numero_factura',
        'fecha_emision',
        'fecha_vencimiento',
        'monto_total_usd',
        'monto_total_bs',
        'monto_pagado_usd',
        'monto_pagado_bs',
        'saldo_pendiente_usd',
        'saldo_pendiente_bs',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'monto_total_usd' => 'decimal:2',
        'monto_total_bs' => 'decimal:2',
        'monto_pagado_usd' => 'decimal:2',
        'monto_pagado_bs' => 'decimal:2',
        'saldo_pendiente_usd' => 'decimal:2',
        'saldo_pendiente_bs' => 'decimal:2',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(CuentaPorCobrarAbono::class, 'cuenta_por_cobrar_id');
    }
}
