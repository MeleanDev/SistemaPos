<?php

namespace App\Service\Inventario;

use App\Models\Almacen;
use App\Models\EmpresaMoneda;
use App\Models\Moto;

class MotoClass
{
    /**
     * Listado de motos en inventario con relaciones
     */
    public function lista(int $empresaId)
    {
        return Moto::with(['almacen', 'proveedor', 'recepcion', 'modeloMoto'])
            ->select('motos.*')
            ->where('motos.empresa_id', $empresaId)
            ->where('motos.estado', '!=', 'anulada')
            ->orderBy('motos.id', 'desc');
    }

    /**
     * Catálogos para el módulo de motos (almacenes activos y tasa vigente)
     */
    public function catalogos(int $empresaId): array
    {
        $almacenes = Almacen::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('nombre', 'asc')
            ->get(['id', 'nombre', 'codigo']);

        $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)
            ->where('codigo', 'USD')
            ->first();

        $monedaPrincipal = EmpresaMoneda::where('empresa_id', $empresaId)
            ->where('es_principal', true)
            ->first();

        return [
            'almacenes' => $almacenes,
            'tasa_bcv' => $monedaUsd && $monedaUsd->tasa_cambio > 0 ? (float) $monedaUsd->tasa_cambio : 1.0,
            'moneda_simbolo' => $monedaPrincipal ? $monedaPrincipal->simbolo : 'Bs.',
        ];
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
        $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)
            ->where('codigo', 'USD')
            ->first();
        $tasaBcv = $monedaUsd && $monedaUsd->tasa_cambio > 0 ? (float) $monedaUsd->tasa_cambio : 1.0;

        $costoUsd = isset($datos['precio_costo_usd']) ? (float) $datos['precio_costo_usd'] : (float) $moto->precio_costo_usd;
        $detalUsd = isset($datos['precio_detal_usd']) ? (float) $datos['precio_detal_usd'] : (float) $moto->precio_detal_usd;
        $mayorUsd = isset($datos['precio_mayorista_usd']) ? (float) $datos['precio_mayorista_usd'] : (float) $moto->precio_mayorista_usd;

        $costoBs = isset($datos['precio_costo_bs']) ? (float) $datos['precio_costo_bs'] : round($costoUsd * $tasaBcv, 2);
        $detalBs = isset($datos['precio_detal_bs']) ? (float) $datos['precio_detal_bs'] : round($detalUsd * $tasaBcv, 2);
        $mayorBs = isset($datos['precio_mayorista_bs']) ? (float) $datos['precio_mayorista_bs'] : round($mayorUsd * $tasaBcv, 2);

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
            'precio_costo_usd' => $costoUsd,
            'precio_costo_bs' => $costoBs,
            'margen_detal' => $datos['margen_detal'] ?? $moto->margen_detal,
            'precio_detal_usd' => $detalUsd,
            'precio_detal_bs' => $detalBs,
            'margen_mayorista' => $datos['margen_mayorista'] ?? $moto->margen_mayorista,
            'precio_mayorista_usd' => $mayorUsd,
            'precio_mayorista_bs' => $mayorBs,
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
