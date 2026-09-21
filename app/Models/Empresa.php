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
        'maneja_motos',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'maneja_motos' => 'boolean',
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

    /**
     * Productos y servicios de esta empresa
     */
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'empresa_id');
    }

    /**
     * Monedas y tasas de cambio configuradas para esta empresa
     */
    public function monedas(): HasMany
    {
        return $this->hasMany(EmpresaMoneda::class, 'empresa_id');
    }

    /**
     * Servicios registrados para esta empresa
     */
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class, 'empresa_id');
    }

    /**
     * Motos registradas para esta empresa
     */
    public function motos(): HasMany
    {
        return $this->hasMany(Moto::class, 'empresa_id');
    }

    /**
     * Recepciones de motos de esta empresa
     */
    public function recepcionMotos(): HasMany
    {
        return $this->hasMany(RecepcionMoto::class, 'empresa_id');
    }
}
