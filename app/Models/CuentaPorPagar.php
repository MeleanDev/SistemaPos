<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaPorPagar extends Model
{
    protected $table = 'cuentas_por_pagar';

    protected $fillable = [
        'empresa_id',
        'proveedor_id',
        'recepcion_id',
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

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_vencimiento' => 'date',
            'monto_total_usd' => 'decimal:2',
            'monto_total_bs' => 'decimal:2',
            'monto_pagado_usd' => 'decimal:2',
            'monto_pagado_bs' => 'decimal:2',
            'saldo_pendiente_usd' => 'decimal:2',
            'saldo_pendiente_bs' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(Recepcion::class, 'recepcion_id');
    }
}
