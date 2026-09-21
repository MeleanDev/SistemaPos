<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaPago extends Model
{
    use HasFactory;

    protected $table = 'venta_pagos';

    protected $fillable = [
        'venta_id',
        'metodo_pago_id',
        'moneda',
        'tasa_cambio',
        'monto_origen',
        'monto_usd',
        'monto_bs',
        'referencia',
    ];

    protected $casts = [
        'tasa_cambio' => 'decimal:4',
        'monto_origen' => 'decimal:2',
        'monto_usd' => 'decimal:2',
        'monto_bs' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
}
