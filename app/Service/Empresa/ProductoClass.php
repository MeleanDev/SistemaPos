<?php

namespace App\Service\Empresa;

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\EmpresaMoneda;
use App\Models\Producto;
use App\Models\ProductoCodigoBarra;
use App\Models\ProductoProveedor;
use App\Models\ProductoStockAlmacen;
use App\Models\Proveedor;
use Illuminate\Support\Facades\DB;

class ProductoClass
{
    /**
     * Query de productos activos para DataTables
     */
    public function lista(int $empresaId)
    {
        return Producto::with(['categoria', 'codigosBarra', 'stockAlmacenes.almacen'])
            ->where('empresa_id', $empresaId)
            ->where('estado', true);
    }

    /**
     * Obtener catálogos necesarios para los formularios
     */
    public function catalogos(int $empresaId): array
    {
        $tasaUsd = EmpresaMoneda::where('empresa_id', $empresaId)
            ->where('codigo', 'USD')
            ->value('tasa_cambio') ?? 1.0000;

        return [
            'categorias' => Categoria::where('empresa_id', $empresaId)
                ->where('estado', true)
                ->orderBy('nombre', 'asc')
                ->get(['id', 'codigo', 'nombre']),
            'proveedores' => Proveedor::where('empresa_id', $empresaId)
                ->where('estado', true)
                ->orderBy('nombre', 'asc')
                ->get(['id', 'rif', 'nombre', 'razon_social']),
            'almacenes' => Almacen::where('empresa_id', $empresaId)
                ->where('estado', true)
                ->orderBy('nombre', 'asc')
                ->get(['id', 'codigo', 'nombre']),
            'monedas' => config('pos.monedas', []),
            'tasa_usd' => (float) $tasaUsd,
        ];
    }

    /**
     * Ficha técnica 360° y detalle completo de un producto
     */
    public function detalle(int $id, int $empresaId): Producto
    {
        return Producto::with([
            'categoria',
            'codigosBarra',
            'productoProveedores.proveedor',
            'stockAlmacenes.almacen',
        ])->where('empresa_id', $empresaId)->findOrFail($id);
    }

    /**
     * Guardar nuevo producto o reactivar existente
     */
    public function guardar(array $datos, int $empresaId): Producto
    {
        return DB::transaction(function () use ($datos, $empresaId) {
            $datos = $this->prepararDatos($datos, $empresaId);

            // Buscar si existía inactivo para reactivar
            $existenteInactivo = Producto::where('empresa_id', $empresaId)
                ->where('estado', false)
                ->where(function ($q) use ($datos) {
                    $q->where('codigo_interno', $datos['codigo_interno'])
                        ->orWhere('nombre', $datos['nombre']);
                })
                ->first();

            if ($existenteInactivo) {
                $datos['estado'] = true;
                $existenteInactivo->update($datos);
                $producto = $existenteInactivo;
            } else {
                $datos['estado'] = true;
                $producto = Producto::create($datos);
            }

            // Sincronizar Códigos de Barra (Opcionales)
            $this->sincronizarCodigosBarra($producto, $datos['codigos_barra'] ?? [], $empresaId);

            // Sincronizar Proveedores (Opcionales)
            $this->sincronizarProveedores($producto, $datos['proveedores'] ?? []);

            // Inicializar registros de stock en todos los almacenes activos de la empresa
            $this->inicializarStockAlmacenes($producto, $empresaId);

            return $producto->fresh(['categoria', 'codigosBarra', 'stockAlmacenes.almacen']);
        });
    }

    /**
     * Actualizar producto existente
     */
    public function actualizar(array $datos, int $id, int $empresaId): Producto
    {
        return DB::transaction(function () use ($datos, $id, $empresaId) {
            $datos = $this->prepararDatos($datos, $empresaId);
            $producto = Producto::where('empresa_id', $empresaId)->findOrFail($id);

            $producto->update($datos);

            // Sincronizar Códigos de Barra
            $this->sincronizarCodigosBarra($producto, $datos['codigos_barra'] ?? [], $empresaId);

            // Sincronizar Proveedores
            $this->sincronizarProveedores($producto, $datos['proveedores'] ?? []);

            // Asegurar que tenga registro en todos los almacenes activos
            $this->inicializarStockAlmacenes($producto, $empresaId);

            return $producto->fresh(['categoria', 'codigosBarra', 'stockAlmacenes.almacen']);
        });
    }

    /**
     * Desactivar producto (Borrado Lógico)
     */
    public function eliminar(int $id, int $empresaId): Producto
    {
        $producto = Producto::where('empresa_id', $empresaId)->findOrFail($id);
        $producto->estado = false;
        $producto->save();

        return $producto;
    }

    /**
     * Sincronizar códigos de barra opcionales
     */
    private function sincronizarCodigosBarra(Producto $producto, array $codigos, int $empresaId): void
    {
        $producto->codigosBarra()->delete();

        foreach ($codigos as $item) {
            $codigo = is_array($item) ? ($item['codigo'] ?? null) : $item;
            $descripcion = is_array($item) ? ($item['descripcion'] ?? null) : null;

            if (! empty($codigo)) {
                ProductoCodigoBarra::create([
                    'producto_id' => $producto->id,
                    'empresa_id' => $empresaId,
                    'codigo_barra' => trim($codigo),
                    'descripcion' => $descripcion ? trim($descripcion) : null,
                    'es_principal' => false,
                ]);
            }
        }
    }

    /**
     * Sincronizar proveedores asociados
     */
    private function sincronizarProveedores(Producto $producto, array $proveedores): void
    {
        $producto->productoProveedores()->delete();

        foreach ($proveedores as $item) {
            $proveedorId = $item['proveedor_id'] ?? null;
            if ($proveedorId) {
                ProductoProveedor::create([
                    'producto_id' => $producto->id,
                    'proveedor_id' => $proveedorId,
                    'codigo_proveedor' => $item['codigo_proveedor'] ?? null,
                    'ultimo_costo_usd' => $item['ultimo_costo_usd'] ?? null,
                    'ultimo_costo_bs' => $item['ultimo_costo_bs'] ?? null,
                ]);
            }
        }
    }

    /**
     * Inicializar balance de stock en almacenes de la empresa
     */
    private function inicializarStockAlmacenes(Producto $producto, int $empresaId): void
    {
        if ($producto->tipo === 'servicio') {
            return;
        }

        $almacenes = Almacen::where('empresa_id', $empresaId)->where('estado', true)->pluck('id');

        foreach ($almacenes as $almacenId) {
            ProductoStockAlmacen::firstOrCreate(
                [
                    'producto_id' => $producto->id,
                    'almacen_id' => $almacenId,
                ],
                [
                    'cantidad_actual' => 0,
                    'cantidad_reservada' => 0,
                ]
            );
        }
    }

    /**
     * Sanitizar y normalizar valores por defecto antes de persistir
     */
    private function prepararDatos(array $datos, int $empresaId): array
    {
        $tasaUsd = EmpresaMoneda::where('empresa_id', $empresaId)
            ->where('codigo', 'USD')
            ->value('tasa_cambio') ?? 1.0000;

        $datos['empresa_id'] = $empresaId;
        $datos['tipo'] = 'producto';

        $datos['precio_costo_usd'] = ! empty($datos['precio_costo_usd']) ? (float) $datos['precio_costo_usd'] : 0;
        $datos['precio_costo_bs'] = round($datos['precio_costo_usd'] * $tasaUsd, 4);

        $datos['precio_detal_usd'] = ! empty($datos['precio_detal_usd']) ? (float) $datos['precio_detal_usd'] : 0;
        $datos['precio_detal_bs'] = ! empty($datos['precio_detal_bs']) ? (float) $datos['precio_detal_bs'] : 0;
        if ($datos['precio_detal_usd'] > 0) {
            $datos['precio_detal_bs'] = round($datos['precio_detal_usd'] * $tasaUsd, 4);
        } elseif ($datos['precio_detal_bs'] > 0) {
            $datos['precio_detal_usd'] = round($datos['precio_detal_bs'] / $tasaUsd, 4);
        }

        $datos['precio_mayorista_usd'] = ! empty($datos['precio_mayorista_usd']) ? (float) $datos['precio_mayorista_usd'] : 0;
        $datos['precio_mayorista_bs'] = ! empty($datos['precio_mayorista_bs']) ? (float) $datos['precio_mayorista_bs'] : 0;
        if ($datos['precio_mayorista_usd'] > 0) {
            $datos['precio_mayorista_bs'] = round($datos['precio_mayorista_usd'] * $tasaUsd, 4);
        } elseif ($datos['precio_mayorista_bs'] > 0) {
            $datos['precio_mayorista_usd'] = round($datos['precio_mayorista_bs'] / $tasaUsd, 4);
        }

        $datos['tasa_cambio'] = $tasaUsd;

        $datos['aplica_iva'] = filter_var($datos['aplica_iva'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $datos['iva_porcentaje'] = $datos['aplica_iva'] ? ($datos['iva_porcentaje'] ?? 16.00) : 0;
        $datos['aplica_igtf'] = filter_var($datos['aplica_igtf'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $datos['igtf_porcentaje'] = $datos['aplica_igtf'] ? ($datos['igtf_porcentaje'] ?? 3.00) : 0;

        $datos['stock_minimo'] = isset($datos['stock_minimo']) && $datos['stock_minimo'] !== '' ? $datos['stock_minimo'] : 0;
        $datos['stock_maximo'] = isset($datos['stock_maximo']) && $datos['stock_maximo'] !== '' ? $datos['stock_maximo'] : null;

        return $datos;
    }
}
