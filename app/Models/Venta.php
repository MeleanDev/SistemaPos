<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'empresa_id',
        'cliente_id',
        'almacen_id',
        'user_id',
        'codigo',
        'numero_control',
        'tipo_venta',
        'moneda',
        'tasa_cambio',
        'fecha_emision',
        'hora_emision',
        'monto_bruto_usd',
        'monto_bruto_bs',
        'descuento_porcentaje',
        'descuento_usd',
        'descuento_bs',
        'subtotal_neto_usd',
        'subtotal_neto_bs',
        'iva_monto_usd',
        'iva_monto_bs',
        'igtf_porcentaje',
        'igtf_monto_usd',
        'igtf_monto_bs',
        'total_usd',
        'total_bs',
        'condicion_pago',
        'monto_pagado_usd',
        'monto_pagado_bs',
        'vuelto_usd',
        'vuelto_bs',
        'saldo_pendiente_usd',
        'saldo_pendiente_bs',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'tasa_cambio' => 'decimal:4',
        'fecha_emision' => 'date',
        'monto_bruto_usd' => 'decimal:2',
        'monto_bruto_bs' => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'descuento_usd' => 'decimal:2',
        'descuento_bs' => 'decimal:2',
        'subtotal_neto_usd' => 'decimal:2',
        'subtotal_neto_bs' => 'decimal:2',
        'iva_monto_usd' => 'decimal:2',
        'iva_monto_bs' => 'decimal:2',
        'igtf_porcentaje' => 'decimal:2',
        'igtf_monto_usd' => 'decimal:2',
        'igtf_monto_bs' => 'decimal:2',
        'total_usd' => 'decimal:2',
        'total_bs' => 'decimal:2',
        'monto_pagado_usd' => 'decimal:2',
        'monto_pagado_bs' => 'decimal:2',
        'vuelto_usd' => 'decimal:2',
        'vuelto_bs' => 'decimal:2',
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

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(VentaDetalle::class, 'venta_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(VentaPago::class, 'venta_id');
    }

    public function cuentaPorCobrar(): HasOne
    {
        return $this->hasOne(CuentaPorCobrar::class, 'venta_id');
    }

    public function devoluciones(): HasMany
    {
        return $this->hasMany(DevolucionVenta::class, 'venta_id');
    }
}
