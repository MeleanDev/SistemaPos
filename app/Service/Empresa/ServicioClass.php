<?php

namespace App\Service\Empresa;

use App\Models\Categoria;
use App\Models\EmpresaMoneda;
use App\Models\Servicio;
use Illuminate\Support\Facades\DB;

class ServicioClass
{
    /**
     * Query de servicios activos para DataTables
     */
    public function lista(int $empresaId)
    {
        return Servicio::with(['categoria'])
            ->where('empresa_id', $empresaId)
            ->where('estado', true);
    }

    /**
     * Catálogos para formularios de servicios
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
            'tasa_usd' => (float) $tasaUsd,
        ];
    }

    /**
     * Detalle completo de un servicio
     */
    public function detalle(int $id, int $empresaId): Servicio
    {
        return Servicio::with(['categoria'])
            ->where('empresa_id', $empresaId)
            ->findOrFail($id);
    }

    /**
     * Guardar nuevo servicio o reactivar existente
     */
    public function guardar(array $datos, int $empresaId): Servicio
    {
        return DB::transaction(function () use ($datos, $empresaId) {
            $datos = $this->prepararDatos($datos, $empresaId);

            $existenteInactivo = Servicio::where('empresa_id', $empresaId)
                ->where('estado', false)
                ->where(function ($q) use ($datos) {
                    $q->where('codigo', $datos['codigo'])
                        ->orWhere('nombre', $datos['nombre']);
                })
                ->first();

            if ($existenteInactivo) {
                $datos['estado'] = true;
                $existenteInactivo->update($datos);
                $servicio = $existenteInactivo;
            } else {
                $datos['estado'] = true;
                $servicio = Servicio::create($datos);
            }

            return $servicio->fresh(['categoria']);
        });
    }

    /**
     * Actualizar servicio existente
     */
    public function actualizar(array $datos, int $id, int $empresaId): Servicio
    {
        return DB::transaction(function () use ($datos, $id, $empresaId) {
            $datos = $this->prepararDatos($datos, $empresaId);
            $servicio = Servicio::where('empresa_id', $empresaId)->findOrFail($id);

            $servicio->update($datos);

            return $servicio->fresh(['categoria']);
        });
    }

    /**
     * Desactivar servicio (Borrado Lógico)
     */
    public function eliminar(int $id, int $empresaId): Servicio
    {
        $servicio = Servicio::where('empresa_id', $empresaId)->findOrFail($id);
        $servicio->estado = false;
        $servicio->save();

        return $servicio;
    }

    /**
     * Normalizar datos y valores numéricos
     */
    private function prepararDatos(array $datos, int $empresaId): array
    {
        $tasaUsd = EmpresaMoneda::where('empresa_id', $empresaId)
            ->where('codigo', 'USD')
            ->value('tasa_cambio') ?? 1.0000;

        $datos['empresa_id'] = $empresaId;
        $datos['precio_venta_usd'] = ! empty($datos['precio_venta_usd']) ? (float) $datos['precio_venta_usd'] : 0;

        // Auto-calcular conversión a Bolívares según tasa oficial activa
        $datos['precio_venta_bs'] = round($datos['precio_venta_usd'] * $tasaUsd, 4);

        return $datos;
    }
}
