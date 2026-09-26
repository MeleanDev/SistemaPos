<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Moto extends Model
{
    use HasFactory;

    protected $table = 'motos';

    protected $fillable = [
        'empresa_id',
        'almacen_id',
        'proveedor_id',
        'recepcion_moto_id',
        'recepcion_moto_detalle_id',
        'referencia',
        'marca',
        'modelo',
        'anio',
        'color',
        'cilindrada',
        'numero_niv',
        'numero_chasis',
        'numero_motor',
        'certificado_origen',
        'placa',
        'costo_base_usd',
        'costo_base_bs',
        'flete_usd',
        'flete_bs',
        'precio_costo_usd',
        'precio_costo_bs',
        'margen_detal',
        'precio_detal_usd',
        'precio_detal_bs',
        'precio_detal_con_iva_usd',
        'precio_detal_con_iva_bs',
        'margen_mayorista',
        'precio_mayorista_usd',
        'precio_mayorista_bs',
        'precio_mayorista_con_iva_usd',
        'precio_mayorista_con_iva_bs',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'costo_base_usd' => 'decimal:4',
            'costo_base_bs' => 'decimal:4',
            'flete_usd' => 'decimal:4',
            'flete_bs' => 'decimal:4',
            'precio_costo_usd' => 'decimal:4',
            'precio_costo_bs' => 'decimal:4',
            'margen_detal' => 'decimal:2',
            'precio_detal_usd' => 'decimal:4',
            'precio_detal_bs' => 'decimal:4',
            'precio_detal_con_iva_usd' => 'decimal:4',
            'precio_detal_con_iva_bs' => 'decimal:4',
            'margen_mayorista' => 'decimal:2',
            'precio_mayorista_usd' => 'decimal:4',
            'precio_mayorista_bs' => 'decimal:4',
            'precio_mayorista_con_iva_usd' => 'decimal:4',
            'precio_mayorista_con_iva_bs' => 'decimal:4',
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

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(RecepcionMoto::class, 'recepcion_moto_id');
    }

    public function detalle(): BelongsTo
    {
        return $this->belongsTo(RecepcionMotoDetalle::class, 'recepcion_moto_detalle_id');
    }
}
