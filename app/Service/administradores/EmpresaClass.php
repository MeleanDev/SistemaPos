<?php

namespace App\Service\Administradores;

use App\Models\Empresa;

class EmpresaClass
{
    public function lista()
    {
        return Empresa::select(
            'id',
            'rif',
            'nombre',
            'razon_social',
            'direccion',
            'telefono',
            'correo',
            'logo',
            'estado',
            'created_at'
        )->where('estado', true);
    }

    public function detalle($id)
    {
        return Empresa::findOrFail($id);
    }

    public function guardar(array $datos)
    {
        $existenteInactivo = Empresa::where('rif', $datos['rif'])
            ->orWhere('nombre', $datos['nombre'])
            ->orWhere('razon_social', $datos['razon_social'])
            ->first();

        if ($existenteInactivo) {
            $datos['estado'] = true;
            $existenteInactivo->update($datos);

            return $existenteInactivo;
        }

        $datos['estado'] = true;

        return Empresa::create($datos);
    }

    public function actualizar(array $datos, $id)
    {
        $empresa = Empresa::findOrFail($id);
        $empresa->update($datos);

        return $empresa;
    }

    public function eliminar($id)
    {
        $empresa = Empresa::findOrFail($id);
        $empresa->estado = false;
        $empresa->save();

        return $empresa;
    }
}
