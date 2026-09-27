<?php

namespace App\Service\Inventario;

use App\Models\ModeloMoto;
use Illuminate\Support\Facades\DB;

class ModeloMotoClass
{
    /**
     * Listado de modelos de moto para DataTables con stock disponible
     */
    public function lista(int $empresaId)
    {
        return ModeloMoto::withCount([
            'motos as stock_disponible' => function ($query) {
                $query->where('estado', 'disponible');
            },
            'motos as total_unidades' => function ($query) {
                $query->where('estado', '!=', 'anulada');
            },
        ])
            ->where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('id', 'desc');
    }

    /**
     * Catálogo de modelos activos para selectores (Select2)
     */
    public function catalogos(int $empresaId): array
    {
        return ModeloMoto::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('referencia', 'asc')
            ->get([
                'id',
                'referencia',
                'marca',
                'modelo',
                'anio',
                'color',
                'cilindrada',
                'descripcion',
            ])
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'referencia' => (string) $m->referencia,
                    'marca' => $m->marca,
                    'modelo' => $m->modelo,
                    'anio' => $m->anio,
                    'color' => $m->color,
                    'cilindrada' => $m->cilindrada,
                    'descripcion' => $m->descripcion,
                    'nombre_completo' => "{$m->marca} {$m->modelo} ({$m->anio}) - {$m->color} [Ref: #{$m->referencia}]",
                ];
            })
            ->toArray();
    }

    /**
     * Generar la próxima referencia numérica autoincrementable por empresa
     */
    public function generarProximaReferencia(int $empresaId): string
    {
        $referencias = ModeloMoto::where('empresa_id', $empresaId)->pluck('referencia');
        $maxNum = 0;

        foreach ($referencias as $ref) {
            $limpio = trim((string) $ref);
            if (is_numeric($limpio)) {
                $val = (int) $limpio;
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            } elseif (preg_match('/(\d+)/', $limpio, $matches)) {
                $val = (int) $matches[1];
                if ($val > $maxNum) {
                    $maxNum = $val;
                }
            }
        }

        return (string) ($maxNum + 1);
    }

    /**
     * Detalle 360° de un modelo de moto
     */
    public function detalle(int $id, int $empresaId): ModeloMoto
    {
        return ModeloMoto::withCount([
            'motos as stock_disponible' => function ($query) {
                $query->where('estado', 'disponible');
            },
            'motos as total_unidades' => function ($query) {
                $query->where('estado', '!=', 'anulada');
            },
        ])
            ->where('empresa_id', $empresaId)
            ->findOrFail($id);
    }

    /**
     * Guardar nuevo modelo o reactivar inactivo
     */
    public function guardar(array $datos, int $empresaId): ModeloMoto
    {
        return DB::transaction(function () use ($datos, $empresaId) {
            $referencia = ! empty($datos['referencia'])
                ? trim((string) $datos['referencia'])
                : $this->generarProximaReferencia($empresaId);

            $existenteInactivo = ModeloMoto::where('empresa_id', $empresaId)
                ->where('referencia', $referencia)
                ->where('estado', false)
                ->first();

            if ($existenteInactivo) {
                $existenteInactivo->update([
                    'marca' => trim($datos['marca']),
                    'modelo' => trim($datos['modelo']),
                    'anio' => (int) $datos['anio'],
                    'color' => trim($datos['color']),
                    'cilindrada' => trim($datos['cilindrada']),
                    'descripcion' => $datos['descripcion'] ?? null,
                    'estado' => true,
                ]);

                return $existenteInactivo;
            }

            return ModeloMoto::create([
                'empresa_id' => $empresaId,
                'referencia' => $referencia,
                'marca' => trim($datos['marca']),
                'modelo' => trim($datos['modelo']),
                'anio' => (int) $datos['anio'],
                'color' => trim($datos['color']),
                'cilindrada' => trim($datos['cilindrada']),
                'descripcion' => $datos['descripcion'] ?? null,
                'estado' => true,
            ]);
        });
    }

    /**
     * Actualizar datos del modelo de moto
     */
    public function actualizar(array $datos, int $id, int $empresaId): ModeloMoto
    {
        $modelo = ModeloMoto::where('empresa_id', $empresaId)->findOrFail($id);

        $referencia = ! empty($datos['referencia'])
            ? trim((string) $datos['referencia'])
            : $modelo->referencia;

        $modelo->update([
            'referencia' => $referencia,
            'marca' => trim($datos['marca']),
            'modelo' => trim($datos['modelo']),
            'anio' => (int) $datos['anio'],
            'color' => trim($datos['color']),
            'cilindrada' => trim($datos['cilindrada']),
            'descripcion' => $datos['descripcion'] ?? null,
        ]);

        return $modelo;
    }

    /**
     * Desactivar modelo de moto (Borrado Lógico)
     */
    public function eliminar(int $id, int $empresaId): ModeloMoto
    {
        $modelo = ModeloMoto::where('empresa_id', $empresaId)->findOrFail($id);
        $modelo->estado = false;
        $modelo->save();

        return $modelo;
    }
}
