<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DevolucionVentaDetalle extends Model
{
    use HasFactory;

    protected $table = 'devolucion_venta_detalles';

    protected $fillable = [
        'devolucion_venta_id',
        'venta_detalle_id',
        'producto_id',
        'moto_id',
        'servicio_id',
        'almacen_id',
        'nombre_item',
        'serial_identificador',
        'cantidad',
        'precio_unitario_usd',
        'precio_unitario_bs',
        'subtotal_usd',
        'subtotal_bs',
    ];

    protected $casts = [
        'cantidad' => 'decimal:3',
        'precio_unitario_usd' => 'decimal:4',
        'precio_unitario_bs' => 'decimal:4',
        'subtotal_usd' => 'decimal:2',
        'subtotal_bs' => 'decimal:2',
    ];

    public function devolucion(): BelongsTo
    {
        return $this->belongsTo(DevolucionVenta::class, 'devolucion_venta_id');
    }

    public function ventaDetalle(): BelongsTo
    {
        return $this->belongsTo(VentaDetalle::class, 'venta_detalle_id');
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
}
