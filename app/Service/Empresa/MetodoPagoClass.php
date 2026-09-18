<?php

namespace App\Service\Empresa;

use App\Models\MetodoPago;

class MetodoPagoClass
{
    public function lista()
    {
        return MetodoPago::select(
            'id',
            'nombre',
            'descripcion',
            'estado',
            'created_at'
        )->where('estado', true);
    }

    public function detalle($id)
    {
        return MetodoPago::findOrFail($id);
    }

    public function guardar(array $datos)
    {
        $existenteInactivo = MetodoPago::where('nombre', $datos['nombre'])->first();

        if ($existenteInactivo) {
            $datos['estado'] = true;
            $existenteInactivo->update($datos);

            return $existenteInactivo;
        }

        $datos['estado'] = true;

        return MetodoPago::create($datos);
    }

    public function actualizar(array $datos, $id)
    {
        $metodo = MetodoPago::findOrFail($id);
        $metodo->update($datos);

        return $metodo;
    }

    public function eliminar($id)
    {
        $metodo = MetodoPago::findOrFail($id);
        $metodo->estado = false;
        $metodo->save();

        return $metodo;
    }
}
