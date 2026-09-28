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

        $tasaOficial = $monedaUsd && $monedaUsd->tasa_cambio > 0 ? (float) $monedaUsd->tasa_cambio : 1.0;

        return [
            'almacenes' => $almacenes,
            'tasa_bcv' => $tasaOficial,
            'tasa_compra' => $tasaOficial,
            'tasa_venta' => $tasaOficial,
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

        $costoBaseUsd = isset($datos['costo_base_usd']) ? (float) $datos['costo_base_usd'] : (float) ($moto->costo_base_usd ?: $moto->precio_costo_usd);
        $fleteUsd = isset($datos['flete_usd']) ? (float) $datos['flete_usd'] : (float) $moto->flete_usd;
        $ivaPorcentaje = isset($datos['iva_porcentaje']) ? (float) $datos['iva_porcentaje'] : (float) ($moto->iva_porcentaje ?? 16.00);

        $ivaUnitarioUsd = ($ivaPorcentaje > 0) ? ($costoBaseUsd * ($ivaPorcentaje / 100)) : 0;
        $costoTotalUsd = isset($datos['precio_costo_usd']) && (float) $datos['precio_costo_usd'] > 0
            ? (float) $datos['precio_costo_usd']
            : ($costoBaseUsd + $ivaUnitarioUsd + $fleteUsd);

        $costoSinIvaUsd = $costoBaseUsd + $fleteUsd;

        $margenDetal = isset($datos['margen_detal']) ? (float) $datos['margen_detal'] : (float) ($moto->margen_detal ?: 25.00);
        $detalConIvaUsd = isset($datos['precio_detal_con_iva_usd']) && (float) $datos['precio_detal_con_iva_usd'] > 0
            ? (float) $datos['precio_detal_con_iva_usd']
            : ($costoTotalUsd * (1 + $margenDetal / 100));

        $detalSinIvaUsd = isset($datos['precio_detal_usd']) && (float) $datos['precio_detal_usd'] > 0
            ? (float) $datos['precio_detal_usd']
            : ($costoSinIvaUsd * (1 + $margenDetal / 100));

        $margenMayorista = isset($datos['margen_mayorista']) ? (float) $datos['margen_mayorista'] : (float) ($moto->margen_mayorista ?: 15.00);
        $mayorConIvaUsd = isset($datos['precio_mayorista_con_iva_usd']) && (float) $datos['precio_mayorista_con_iva_usd'] > 0
            ? (float) $datos['precio_mayorista_con_iva_usd']
            : ($costoTotalUsd * (1 + $margenMayorista / 100));

        $mayorSinIvaUsd = isset($datos['precio_mayorista_usd']) && (float) $datos['precio_mayorista_usd'] > 0
            ? (float) $datos['precio_mayorista_usd']
            : ($costoSinIvaUsd * (1 + $margenMayorista / 100));

        $costoBaseBs = isset($datos['costo_base_bs']) ? (float) $datos['costo_base_bs'] : round($costoBaseUsd * $tasaBcv, 4);
        $fleteBs = isset($datos['flete_bs']) ? (float) $datos['flete_bs'] : round($fleteUsd * $tasaBcv, 4);
        $costoTotalBs = isset($datos['precio_costo_bs']) ? (float) $datos['precio_costo_bs'] : round($costoTotalUsd * $tasaBcv, 4);

        $detalConIvaBs = isset($datos['precio_detal_con_iva_bs']) ? (float) $datos['precio_detal_con_iva_bs'] : round($detalConIvaUsd * $tasaBcv, 4);
        $detalSinIvaBs = isset($datos['precio_detal_bs']) ? (float) $datos['precio_detal_bs'] : round($detalSinIvaUsd * $tasaBcv, 4);

        $mayorConIvaBs = isset($datos['precio_mayorista_con_iva_bs']) ? (float) $datos['precio_mayorista_con_iva_bs'] : round($mayorConIvaUsd * $tasaBcv, 4);
        $mayorSinIvaBs = isset($datos['precio_mayorista_bs']) ? (float) $datos['precio_mayorista_bs'] : round($mayorSinIvaUsd * $tasaBcv, 4);

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
            'costo_base_usd' => $costoBaseUsd,
            'costo_base_bs' => $costoBaseBs,
            'flete_usd' => $fleteUsd,
            'flete_bs' => $fleteBs,
            'iva_porcentaje' => $ivaPorcentaje,
            'precio_costo_usd' => $costoTotalUsd,
            'precio_costo_bs' => $costoTotalBs,
            'margen_detal' => $margenDetal,
            'precio_detal_usd' => $detalSinIvaUsd,
            'precio_detal_bs' => $detalSinIvaBs,
            'precio_detal_con_iva_usd' => $detalConIvaUsd,
            'precio_detal_con_iva_bs' => $detalConIvaBs,
            'margen_mayorista' => $margenMayorista,
            'precio_mayorista_usd' => $mayorSinIvaUsd,
            'precio_mayorista_bs' => $mayorSinIvaBs,
            'precio_mayorista_con_iva_usd' => $mayorConIvaUsd,
            'precio_mayorista_con_iva_bs' => $mayorConIvaBs,
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
