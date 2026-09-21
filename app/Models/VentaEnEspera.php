<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaEnEspera extends Model
{
    use HasFactory;

    protected $table = 'ventas_en_espera';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'cliente_id',
        'tipo_venta',
        'nota_referencia',
        'datos_json',
        'total_usd',
        'total_bs',
    ];

    protected $casts = [
        'datos_json' => 'array',
        'total_usd' => 'decimal:2',
        'total_bs' => 'decimal:2',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
