<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecepcionMoto extends Model
{
    use HasFactory;

    protected $table = 'recepcion_motos';

    protected $fillable = [
        'empresa_id',
        'almacen_id',
        'proveedor_id',
        'user_id',
        'codigo',
        'tipo_documento',
        'numero_documento',
        'numero_control',
        'moneda_documento',
        'tasa_cambio',
        'tasa_compra',
        'tasa_venta',
        'fecha_emision',
        'fecha_recepcion',
        'condicion_pago',
        'dias_credito',
        'fecha_vencimiento',
        'monto_bruto_usd',
        'monto_bruto_bs',
        'base_imponible_usd',
        'base_imponible_bs',
        'exento_usd',
        'exento_bs',
        'descuento_global_porcentaje',
        'descuento_global_usd',
        'descuento_global_bs',
        'subtotal_usd',
        'subtotal_bs',
        'iva_porcentaje',
        'iva_usd',
        'iva_bs',
        'flete_total_usd',
        'flete_total_bs',
        'incluir_flete_en_factura',
        'total_usd',
        'total_bs',
        'total_unidades',
        'observaciones',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_emision' => 'date',
            'fecha_recepcion' => 'date',
            'fecha_vencimiento' => 'date',
            'tasa_cambio' => 'decimal:4',
            'tasa_compra' => 'decimal:4',
            'tasa_venta' => 'decimal:4',
            'monto_bruto_usd' => 'decimal:2',
            'monto_bruto_bs' => 'decimal:2',
            'base_imponible_usd' => 'decimal:2',
            'base_imponible_bs' => 'decimal:2',
            'exento_usd' => 'decimal:2',
            'exento_bs' => 'decimal:2',
            'descuento_global_porcentaje' => 'decimal:2',
            'descuento_global_usd' => 'decimal:2',
            'descuento_global_bs' => 'decimal:2',
            'subtotal_usd' => 'decimal:2',
            'subtotal_bs' => 'decimal:2',
            'iva_porcentaje' => 'decimal:2',
            'iva_usd' => 'decimal:2',
            'iva_bs' => 'decimal:2',
            'flete_total_usd' => 'decimal:2',
            'flete_total_bs' => 'decimal:2',
            'incluir_flete_en_factura' => 'boolean',
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
        return $this->hasMany(RecepcionMotoDetalle::class, 'recepcion_moto_id');
    }

    public function motos(): HasMany
    {
        return $this->hasMany(Moto::class, 'recepcion_moto_id');
    }
}
