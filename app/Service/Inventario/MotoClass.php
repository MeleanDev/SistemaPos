<?php

namespace App\Service\Inventario;

use App\Models\Moto;

class MotoClass
{
    /**
     * Listado de motos en inventario con relaciones
     */
    public function lista(int $empresaId)
    {
        return Moto::with(['almacen', 'proveedor', 'recepcion'])
            ->where('empresa_id', $empresaId)
            ->where('estado', '!=', 'anulada')
            ->orderBy('id', 'desc');
    }

    /**
     * Detalle 360° de una moto
     */
    public function detalle(int $id, int $empresaId): Moto
    {
        return Moto::with(['almacen', 'proveedor', 'recepcion', 'detalle'])
            ->where('empresa_id', $empresaId)
            ->findOrFail($id);
    }

    /**
     * Actualizar datos o precios de una moto
     */
    public function actualizar(array $datos, int $id, int $empresaId): Moto
    {
        $moto = Moto::where('empresa_id', $empresaId)->findOrFail($id);

        $moto->update([
            'marca' => $datos['marca'] ?? $moto->marca,
            'modelo' => $datos['modelo'] ?? $moto->modelo,
            'referencia' => ! empty($datos['referencia']) ? trim($datos['referencia']) : $moto->referencia,
            'anio' => $datos['anio'] ?? $moto->anio,
            'color' => $datos['color'] ?? $moto->color,
            'cilindrada' => $datos['cilindrada'] ?? $moto->cilindrada,
            'numero_niv' => $datos['numero_niv'] ?? $moto->numero_niv,
            'numero_chasis' => $datos['numero_chasis'] ?? $moto->numero_chasis,
            'numero_motor' => $datos['numero_motor'] ?? $moto->numero_motor,
            'certificado_origen' => $datos['certificado_origen'] ?? $moto->certificado_origen,
            'almacen_id' => $datos['almacen_id'] ?? $moto->almacen_id,
            'placa' => ! empty($datos['placa']) ? strtoupper(trim($datos['placa'])) : $moto->placa,
            'precio_costo_usd' => $datos['precio_costo_usd'] ?? $moto->precio_costo_usd,
            'precio_costo_bs' => $datos['precio_costo_bs'] ?? $moto->precio_costo_bs,
            'margen_detal' => $datos['margen_detal'] ?? $moto->margen_detal,
            'precio_detal_usd' => $datos['precio_detal_usd'] ?? $moto->precio_detal_usd,
            'precio_detal_bs' => $datos['precio_detal_bs'] ?? $moto->precio_detal_bs,
            'margen_mayorista' => $datos['margen_mayorista'] ?? $moto->margen_mayorista,
            'precio_mayorista_usd' => $datos['precio_mayorista_usd'] ?? $moto->precio_mayorista_usd,
            'precio_mayorista_bs' => $datos['precio_mayorista_bs'] ?? $moto->precio_mayorista_bs,
            'estado' => $datos['estado'] ?? $moto->estado,
            'observaciones' => $datos['observaciones'] ?? $moto->observaciones,
        ]);

        return $moto;
    }

    /**
     * Marcar estado de moto (ej. mantenimiento, disponible)
     */
    public function cambiarEstado(int $id, string $nuevoEstado, int $empresaId): Moto
    {
        $moto = Moto::where('empresa_id', $empresaId)->findOrFail($id);
        $moto->estado = $nuevoEstado;
        $moto->save();

        return $moto;
    }
}
