<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'rif',
        'nombre',
        'razon_social',
        'direccion',
        'telefono',
        'correo',
        'logo',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    /**
     * Usuarios asignados a esta empresa
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'empresa_user')
            ->withPivot('es_predeterminada', 'estado')
            ->withTimestamps();
    }

    /**
     * Almacenes de esta empresa
     */
    public function almacenes(): HasMany
    {
        return $this->hasMany(Almacen::class, 'empresa_id');
    }

    /**
     * Categorías de productos de esta empresa
     */
    public function categorias(): HasMany
    {
        return $this->hasMany(Categoria::class, 'empresa_id');
    }
}
