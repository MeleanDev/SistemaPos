<?php

namespace App\Service\Empresa;

use App\Models\Categoria;

class CategoriaClass
{
    /**
     * Query de categorías activas para una empresa (utilizado por DataTables)
     */
    public function lista(int $empresaId)
    {
        return Categoria::select(
            'id',
            'empresa_id',
            'codigo',
            'nombre',
            'descripcion',
            'estado',
            'created_at'
        )->where('estado', true)
            ->where('empresa_id', $empresaId);
    }

    /**
     * Obtener detalle de una categoría por ID y empresa
     */
    public function detalle(int $id, int $empresaId): Categoria
    {
        return Categoria::where('empresa_id', $empresaId)->findOrFail($id);
    }

    /**
     * Guardar o reactivar una categoría
     */
    public function guardar(array $datos, int $empresaId): Categoria
    {
        $datos['empresa_id'] = $empresaId;

        $existenteInactivo = Categoria::where('empresa_id', $empresaId)
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

        return Categoria::create($datos);
    }

    /**
     * Actualizar datos de una categoría
     */
    public function actualizar(array $datos, int $id, int $empresaId): Categoria
    {
        $categoria = Categoria::where('empresa_id', $empresaId)->findOrFail($id);
        $categoria->update($datos);

        return $categoria;
    }

    /**
     * Desactivar categoría (Borrado Lógico / Cambio de Estado)
     */
    public function eliminar(int $id, int $empresaId): Categoria
    {
        $categoria = Categoria::where('empresa_id', $empresaId)->findOrFail($id);
        $categoria->estado = false;
        $categoria->save();

        return $categoria;
    }
}
