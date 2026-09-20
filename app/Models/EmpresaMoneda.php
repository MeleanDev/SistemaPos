<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaMoneda extends Model
{
    protected $table = 'empresa_monedas';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'simbolo',
        'tasa_cambio',
        'es_principal',
        'estado',
        'ultima_actualizacion_tasa',
    ];

    protected function casts(): array
    {
        return [
            'tasa_cambio' => 'decimal:4',
            'es_principal' => 'boolean',
            'estado' => 'boolean',
            'ultima_actualizacion_tasa' => 'datetime',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
