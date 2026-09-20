<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Recepcion extends Model
{
    protected $table = 'recepciones';

    protected $fillable = [
        'empresa_id',
        'almacen_id',
        'proveedor_id',
        'user_id',
        'codigo',
        'tipo_documento',
        'numero_documento',
        'numero_control',
        'fecha_emision',
        'fecha_recepcion',
        'condicion_pago',
        'dias_credito',
        'fecha_vencimiento',
        'tasa_cambio',
        'moneda_documento',
        'monto_bruto_usd',
        'monto_bruto_bs',
        'descuento_global_porcentaje',
        'descuento_global_usd',
        'descuento_global_bs',
        'subtotal_usd',
        'iva_usd',
        'total_usd',
        'total_bs',
        'observaciones',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_recepcion' => 'date',
            'fecha_vencimiento' => 'date',
            'dias_credito' => 'integer',
            'tasa_cambio' => 'decimal:4',
            'monto_bruto_usd' => 'decimal:2',
            'monto_bruto_bs' => 'decimal:2',
            'descuento_global_porcentaje' => 'decimal:2',
            'descuento_global_usd' => 'decimal:2',
            'descuento_global_bs' => 'decimal:2',
            'subtotal_usd' => 'decimal:2',
            'iva_usd' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'total_bs' => 'decimal:2',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(RecepcionDetalle::class, 'recepcion_id');
    }

    public function cuentaPorPagar(): HasOne
    {
        return $this->hasOne(CuentaPorPagar::class, 'recepcion_id');
    }
}
