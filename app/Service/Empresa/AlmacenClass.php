<?php

namespace App\Service\Empresa;

use App\Models\Almacen;
use Illuminate\Database\Eloquent\Collection;

class AlmacenClass
{
    /**
     * Listar todos los almacenes activos de una empresa
     */
    public function lista(int $empresaId): Collection
    {
        return Almacen::select('id', 'empresa_id', 'codigo', 'nombre', 'direccion', 'estado', 'created_at')
            ->where('estado', true)
            ->where('empresa_id', $empresaId)
            ->orderBy('nombre', 'asc')
            ->get();
    }

    /**
     * Obtener detalle de un almacén por ID y empresa
     */
    public function detalle(int $id, int $empresaId): Almacen
    {
        return Almacen::where('empresa_id', $empresaId)->findOrFail($id);
    }

    /**
     * Guardar o reactivar un almacén
     */
    public function guardar(array $datos, int $empresaId): Almacen
    {
        $datos['empresa_id'] = $empresaId;

        $existenteInactivo = Almacen::where('empresa_id', $empresaId)
            ->where('estado', false)
            ->where(function ($q) use ($datos) {
                $q->where('codigo', $datos['codigo'])
                    ->orWhere('nombre', $datos['nombre']);
            })
            ->first();

        if ($existenteInactivo) {
            $datos['estado'] = true;
            $existenteInactivo->update($datos);

            return $existenteInactivo;
        }

        $datos['estado'] = true;

        return Almacen::create($datos);
    }

    /**
     * Actualizar datos de un almacén
     */
    public function actualizar(array $datos, int $id, int $empresaId): Almacen
    {
        $almacen = Almacen::where('empresa_id', $empresaId)->findOrFail($id);
        $almacen->update($datos);

        return $almacen;
    }

    /**
     * Borrado lógico de un almacén
     */
    public function eliminar(int $id, int $empresaId): Almacen
    {
        $almacen = Almacen::where('empresa_id', $empresaId)->findOrFail($id);
        $almacen->estado = false;
        $almacen->save();

        return $almacen;
    }
}
