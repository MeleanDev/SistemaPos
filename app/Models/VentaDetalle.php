<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VentaDetalle extends Model
{
    use HasFactory;

    protected $table = 'venta_detalles';

    protected $fillable = [
        'venta_id',
        'producto_id',
        'moto_id',
        'servicio_id',
        'almacen_id',
        'tipo_item',
        'nombre_item',
        'serial_identificador',
        'cantidad',
        'costo_unitario_usd',
        'costo_unitario_bs',
        'precio_unitario_usd',
        'precio_unitario_bs',
        'descuento_porcentaje',
        'descuento_usd',
        'descuento_bs',
        'aplica_iva',
        'iva_porcentaje',
        'iva_monto_usd',
        'iva_monto_bs',
        'subtotal_usd',
        'subtotal_bs',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'costo_unitario_usd' => 'decimal:4',
        'costo_unitario_bs' => 'decimal:4',
        'precio_unitario_usd' => 'decimal:4',
        'precio_unitario_bs' => 'decimal:4',
        'descuento_porcentaje' => 'decimal:2',
        'descuento_usd' => 'decimal:2',
        'descuento_bs' => 'decimal:2',
        'aplica_iva' => 'boolean',
        'iva_porcentaje' => 'decimal:2',
        'iva_monto_usd' => 'decimal:2',
        'iva_monto_bs' => 'decimal:2',
        'subtotal_usd' => 'decimal:2',
        'subtotal_bs' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function moto(): BelongsTo
    {
        return $this->belongsTo(Moto::class, 'moto_id');
    }

    public function servicio(): BelongsTo
    {
        return $this->belongsTo(Servicio::class, 'servicio_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function devolucionDetalles(): HasMany
    {
        return $this->hasMany(DevolucionVentaDetalle::class, 'venta_detalle_id');
    }
}
