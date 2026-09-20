<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servicio extends Model
{
    protected $table = 'servicios';

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'codigo',
        'nombre',
        'descripcion',
        'precio_costo_usd',
        'precio_costo_bs',
        'precio_venta_usd',
        'precio_venta_bs',
        'aplica_iva',
        'iva_porcentaje',
        'aplica_igtf',
        'igtf_porcentaje',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'precio_costo_usd' => 'decimal:4',
            'precio_costo_bs' => 'decimal:4',
            'precio_venta_usd' => 'decimal:4',
            'precio_venta_bs' => 'decimal:4',
            'aplica_iva' => 'boolean',
            'iva_porcentaje' => 'decimal:2',
            'aplica_igtf' => 'boolean',
            'igtf_porcentaje' => 'decimal:2',
            'estado' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }
}
