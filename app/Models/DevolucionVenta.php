<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DevolucionVenta extends Model
{
    use HasFactory;

    protected $table = 'devoluciones_venta';

    protected $fillable = [
        'empresa_id',
        'venta_id',
        'user_id',
        'codigo',
        'motivo',
        'total_devuelto_usd',
        'total_devuelto_bs',
        'tipo_reembolso',
    ];

    protected $casts = [
        'total_devuelto_usd' => 'decimal:2',
        'total_devuelto_bs' => 'decimal:2',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DevolucionVentaDetalle::class, 'devolucion_venta_id');
    }
}
