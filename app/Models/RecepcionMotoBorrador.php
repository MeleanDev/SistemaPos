<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecepcionMotoBorrador extends Model
{
    use HasFactory;

    protected $table = 'recepcion_motos_borradores';

    protected $fillable = [
        'empresa_id',
        'user_id',
        'proveedor_id',
        'referencia',
        'numero_documento',
        'total_unidades',
        'datos_json',
    ];

    protected $casts = [
        'datos_json' => 'array',
        'total_unidades' => 'integer',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id');
    }
}
