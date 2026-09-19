<?php

namespace App\Service\Empresa;

use App\Models\Proveedor;

class ProveedorClass
{
    public function lista(int $empresaId)
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

    public function detalle($id, int $empresaId)
    {
        return Proveedor::where('empresa_id', $empresaId)->findOrFail($id);
    }

    public function guardar(array $datos, int $empresaId)
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

    public function actualizar(array $datos, $id, int $empresaId)
    {
        $proveedor = Proveedor::where('empresa_id', $empresaId)->findOrFail($id);
        $proveedor->update($datos);

        return $proveedor;
    }

    public function eliminar($id, int $empresaId)
    {
        $proveedor = Proveedor::where('empresa_id', $empresaId)->findOrFail($id);
        $proveedor->estado = false;
        $proveedor->save();

        return $proveedor;
    }
}
