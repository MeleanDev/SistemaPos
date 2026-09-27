<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModeloMoto extends Model
{
    use HasFactory;

    protected $table = 'modelos_motos';

    protected $fillable = [
        'empresa_id',
        'referencia',
        'marca',
        'modelo',
        'anio',
        'color',
        'cilindrada',
        'descripcion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'estado' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function motos(): HasMany
    {
        return $this->hasMany(Moto::class, 'modelo_moto_id');
    }

    public function detallesRecepcion(): HasMany
    {
        return $this->hasMany(RecepcionMotoDetalle::class, 'modelo_moto_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->marca} {$this->modelo} ({$this->anio}) - {$this->color} [Ref: #{$this->referencia}]";
    }
}
