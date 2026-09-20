<?php

namespace App\Service\Inventario;

use App\Models\Kardex;
use App\Models\ProductoStockAlmacen;
use Illuminate\Support\Facades\Auth;

class KardexClass
{
    /**
     * Registrar un movimiento inmutable en el Kardex y actualizar stock en el almacén
     */
    public function registrarMovimiento(array $datos): Kardex
    {
        $empresaId = $datos['empresa_id'] ?? (Auth::user()?->empresaActiva()?->id ?? session('empresa_activa_id'));
        $almacenId = $datos['almacen_id'];
        $productoId = $datos['producto_id'];
        $userId = $datos['user_id'] ?? Auth::id();
        $tipoMovimiento = $datos['tipo_movimiento']; // entrada_recepcion, salida_venta, traslado_salida, traslado_entrada, ajuste_positivo, ajuste_negativo, anulacion_recepcion, anulacion_venta
        $documentoTipo = $datos['documento_tipo']; // recepcion, venta, traslado, ajuste
        $documentoId = $datos['documento_id'];
        $cantidad = (float) $datos['cantidad'];
        $costoUnitarioUsd = (float) ($datos['costo_unitario_usd'] ?? 0);
        $costoUnitarioBs = (float) ($datos['costo_unitario_bs'] ?? 0);
        $motivo = $datos['motivo'] ?? null;

        // Obtener o inicializar registro de stock en el almacén
        $stockAlmacen = ProductoStockAlmacen::firstOrCreate(
            [
                'producto_id' => $productoId,
                'almacen_id' => $almacenId,
            ],
            [
                'cantidad_actual' => 0,
                'cantidad_reservada' => 0,
            ]
        );

        $stockAnterior = (float) $stockAlmacen->cantidad_actual;

        // Determinar impacto de stock según el tipo de movimiento
        $esEntrada = in_array($tipoMovimiento, [
            'entrada_recepcion',
            'traslado_entrada',
            'ajuste_positivo',
            'anulacion_venta',
        ]);

        if ($esEntrada) {
            $stockNuevo = $stockAnterior + $cantidad;
        } else {
            $stockNuevo = max(0, $stockAnterior - $cantidad);
        }

        // Actualizar stock del almacén
        $stockAlmacen->cantidad_actual = $stockNuevo;
        $stockAlmacen->save();

        // Crear asiento de Kardex
        return Kardex::create([
            'empresa_id' => $empresaId,
            'almacen_id' => $almacenId,
            'producto_id' => $productoId,
            'user_id' => $userId,
            'tipo_movimiento' => $tipoMovimiento,
            'documento_tipo' => $documentoTipo,
            'documento_id' => $documentoId,
            'cantidad' => $cantidad,
            'costo_unitario_usd' => $costoUnitarioUsd,
            'costo_unitario_bs' => $costoUnitarioBs,
            'stock_anterior' => $stockAnterior,
            'stock_nuevo' => $stockNuevo,
            'motivo' => $motivo,
        ]);
    }
}
