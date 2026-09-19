<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Categoria extends Model
{
    protected $table = 'categorias';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'descripcion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    /**
     * Empresa a la que pertenece la categoría
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
