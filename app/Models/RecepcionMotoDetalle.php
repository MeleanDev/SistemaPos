<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecepcionMotoDetalle extends Model
{
    use HasFactory;

    protected $table = 'recepcion_moto_detalles';

    protected $fillable = [
        'recepcion_moto_id',
        'almacen_id',
        'referencia',
        'marca',
        'modelo',
        'anio',
        'color',
        'cilindrada',
        'cantidad',
        'costo_unitario_usd',
        'costo_unitario_bs',
        'descuento_porcentaje',
        'descuento_usd',
        'descuento_bs',
        'aplica_iva',
        'iva_porcentaje',
        'iva_monto_usd',
        'iva_monto_bs',
        'margen_detal',
        'precio_detal_usd',
        'precio_detal_bs',
        'margen_mayorista',
        'precio_mayorista_usd',
        'precio_mayorista_bs',
        'subtotal_usd',
        'subtotal_bs',
        'total_usd',
        'total_bs',
    ];

    protected function casts(): array
    {
        return [
            'aplica_iva' => 'boolean',
            'costo_unitario_usd' => 'decimal:4',
            'costo_unitario_bs' => 'decimal:4',
            'descuento_porcentaje' => 'decimal:2',
            'descuento_usd' => 'decimal:4',
            'descuento_bs' => 'decimal:4',
            'iva_porcentaje' => 'decimal:2',
            'iva_monto_usd' => 'decimal:4',
            'iva_monto_bs' => 'decimal:4',
            'margen_detal' => 'decimal:2',
            'precio_detal_usd' => 'decimal:4',
            'precio_detal_bs' => 'decimal:4',
            'margen_mayorista' => 'decimal:2',
            'precio_mayorista_usd' => 'decimal:4',
            'precio_mayorista_bs' => 'decimal:4',
            'subtotal_usd' => 'decimal:2',
            'subtotal_bs' => 'decimal:2',
            'total_usd' => 'decimal:2',
            'total_bs' => 'decimal:2',
        ];
    }

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(RecepcionMoto::class, 'recepcion_moto_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function motos(): HasMany
    {
        return $this->hasMany(Moto::class, 'recepcion_moto_detalle_id');
    }
}
