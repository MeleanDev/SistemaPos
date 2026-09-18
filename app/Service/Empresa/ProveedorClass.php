<?php

namespace App\Service\Empresa;

use App\Models\Proveedor;

class ProveedorClass
{
    public function lista()
    {
        return Proveedor::select(
            'id',
            'rif',
            'nombre',
            'razon_social',
            'nombre_contacto',
            'telefono',
            'correo',
            'direccion',
            'estado'
        )->where('estado', true);
    }

    public function detalle($id)
    {
        return Proveedor::findOrFail($id);
    }

    public function guardar(array $datos)
    {
        $existenteInactivo = Proveedor::where('rif', $datos['rif'])
            ->orWhere('nombre', $datos['nombre'])
            ->orWhere('razon_social', $datos['razon_social'])
            ->first();

        if ($existenteInactivo) {
            $datos['estado'] = true;
            $existenteInactivo->update($datos);

            return $existenteInactivo;
        }

        $datos['estado'] = true;

        return Proveedor::create($datos);
    }

    public function actualizar(array $datos, $id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->update($datos);

        return $proveedor;
    }

    public function eliminar($id)
    {
        $proveedor = Proveedor::findOrFail($id);
        $proveedor->estado = false;
        $proveedor->save();

        return $proveedor;
    }
}
