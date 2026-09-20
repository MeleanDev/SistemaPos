<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    protected $table = 'productos';

    protected $fillable = [
        'empresa_id',
        'categoria_id',
        'tipo',
        'codigo_interno',
        'nombre',
        'descripcion',
        'unidad_medida',
        'stock_minimo',
        'stock_maximo',
        'precio_costo_usd',
        'precio_costo_bs',
        'precio_detal_usd',
        'precio_detal_bs',
        'precio_mayorista_usd',
        'precio_mayorista_bs',
        'ultimo_margen_detal',
        'ultimo_margen_mayorista',
        'tasa_cambio',
        'aplica_iva',
        'iva_porcentaje',
        'aplica_igtf',
        'igtf_porcentaje',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'stock_minimo' => 'decimal:2',
            'stock_maximo' => 'decimal:2',
            'precio_costo_usd' => 'decimal:4',
            'precio_costo_bs' => 'decimal:4',
            'precio_detal_usd' => 'decimal:4',
            'precio_detal_bs' => 'decimal:4',
            'precio_mayorista_usd' => 'decimal:4',
            'precio_mayorista_bs' => 'decimal:4',
            'ultimo_margen_detal' => 'decimal:2',
            'ultimo_margen_mayorista' => 'decimal:2',
            'tasa_cambio' => 'decimal:4',
            'aplica_iva' => 'boolean',
            'iva_porcentaje' => 'decimal:2',
            'aplica_igtf' => 'boolean',
            'igtf_porcentaje' => 'decimal:2',
            'estado' => 'boolean',
        ];
    }

    /**
     * Empresa a la que pertenece el producto
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    /**
     * Categoría a la que pertenece el producto
     */
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class, 'categoria_id');
    }

    /**
     * Códigos de barra asociados al producto
     */
    public function codigosBarra(): HasMany
    {
        return $this->hasMany(ProductoCodigoBarra::class, 'producto_id');
    }

    /**
     * Proveedores que suministran este producto
     */
    public function proveedores(): BelongsToMany
    {
        return $this->belongsToMany(Proveedor::class, 'producto_proveedores')
            ->withPivot('codigo_proveedor', 'ultimo_costo_usd', 'ultimo_costo_bs')
            ->withTimestamps();
    }

    /**
     * Registros de proveedores intermedios
     */
    public function productoProveedores(): HasMany
    {
        return $this->hasMany(ProductoProveedor::class, 'producto_id');
    }

    /**
     * Existencias de stock por almacén
     */
    public function stockAlmacenes(): HasMany
    {
        return $this->hasMany(ProductoStockAlmacen::class, 'producto_id');
    }

    /**
     * Obtener stock total consolidado de todos los almacenes
     */
    public function getStockTotalAttribute(): float
    {
        if ($this->tipo === 'servicio') {
            return 0;
        }

        return (float) $this->stockAlmacenes()->sum('cantidad_actual');
    }
}
