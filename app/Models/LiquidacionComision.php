<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LiquidacionComision extends Model
{
    use HasFactory;

    protected $table = 'liquidaciones_comision';

    protected $fillable = [
        'empresa_id',
        'vendedor_id',
        'user_id',
        'caja_turno_id',
        'metodo_pago_id',
        'codigo',
        'fecha_inicio',
        'fecha_fin',
        'cantidad_ventas',
        'total_comision_usd',
        'total_comision_bs',
        'pagado_por_caja',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'cantidad_ventas' => 'integer',
            'total_comision_usd' => 'decimal:2',
            'total_comision_bs' => 'decimal:2',
            'pagado_por_caja' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function vendedor(): BelongsTo
    {
        return $this->belongsTo(Vendedor::class, 'vendedor_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cajaTurno(): BelongsTo
    {
        return $this->belongsTo(CajaTurno::class, 'caja_turno_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'liquidacion_comision_id');
    }
}
