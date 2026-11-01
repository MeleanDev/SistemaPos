<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    protected $table = 'metodos_pago';

    protected $fillable = [
        'nombre',
        'descripcion',
        'es_efectivo',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'es_efectivo' => 'boolean',
            'estado' => 'boolean',
        ];
    }
}
