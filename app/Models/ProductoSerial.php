<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoSerial extends Model
{
    use HasFactory;

    protected $table = 'producto_seriales';

    protected $fillable = [
        'empresa_id',
        'producto_id',
        'almacen_id',
        'recepcion_id',
        'recepcion_detalle_id',
        'numero_serial',
        'variante_color',
        'estado',
        'venta_id',
        'venta_detalle_id',
        'observaciones',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function recepcion(): BelongsTo
    {
        return $this->belongsTo(Recepcion::class, 'recepcion_id');
    }

    public function recepcionDetalle(): BelongsTo
    {
        return $this->belongsTo(RecepcionDetalle::class, 'recepcion_detalle_id');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function ventaDetalle(): BelongsTo
    {
        return $this->belongsTo(VentaDetalle::class, 'venta_detalle_id');
    }

    public function scopeDisponible(Builder $query, ?int $almacenId = null): Builder
    {
        $q = $query->where('estado', 'disponible');
        if ($almacenId) {
            $q->where('almacen_id', $almacenId);
        }

        return $q;
    }

    public function scopePorEmpresa(Builder $query, int $empresaId): Builder
    {
        return $query->where('empresa_id', $empresaId);
    }
}
