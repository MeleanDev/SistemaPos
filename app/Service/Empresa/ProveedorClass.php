<?php

namespace App\Service\Empresa;

use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Builder;

class ProveedorClass
{
    /**
     * Listado de proveedores activos por empresa (Query para DataTables)
     */
    public function lista(int $empresaId): Builder
    {
        return Proveedor::select(
            'id',
            'empresa_id',
            'rif',
            'nombre',
            'razon_social',
            'nombre_contacto',
            'telefono',
            'correo',
            'direccion',
            'estado'
        )->where('estado', true)
            ->where('empresa_id', $empresaId);
    }

    /**
     * Detalle de un proveedor por ID y empresa
     */
    public function detalle(int $id, int $empresaId): Proveedor
    {
        return Proveedor::where('empresa_id', $empresaId)->findOrFail($id);
    }

    /**
     * Guardar nuevo proveedor o reactivar existente
     */
    public function guardar(array $datos, int $empresaId): Proveedor
    {
        $datos['empresa_id'] = $empresaId;

        $existenteInactivo = Proveedor::where('empresa_id', $empresaId)
            ->where('estado', false)
            ->where(function ($q) use ($datos) {
                $q->where('rif', $datos['rif'])
                    ->orWhere('nombre', $datos['nombre'])
                    ->orWhere('razon_social', $datos['razon_social']);
            })
            ->first();

        if ($existenteInactivo) {
            $datos['estado'] = true;
            $existenteInactivo->update($datos);

            return $existenteInactivo;
        }

        $datos['estado'] = true;

        return Proveedor::create($datos);
    }

    /**
     * Actualizar proveedor existente
     */
    public function actualizar(array $datos, int $id, int $empresaId): Proveedor
    {
        $proveedor = Proveedor::where('empresa_id', $empresaId)->findOrFail($id);
        $proveedor->update($datos);

        return $proveedor;
    }

    /**
     * Borrado lógico de un proveedor
     */
    public function eliminar(int $id, int $empresaId): Proveedor
    {
        $proveedor = Proveedor::where('empresa_id', $empresaId)->findOrFail($id);
        $proveedor->estado = false;
        $proveedor->save();

        return $proveedor;
    }
}
