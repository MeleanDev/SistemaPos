<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoCodigoBarra extends Model
{
    protected $table = 'producto_codigos_barra';

    protected $fillable = [
        'producto_id',
        'empresa_id',
        'codigo_barra',
        'descripcion',
        'es_principal',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }
}
