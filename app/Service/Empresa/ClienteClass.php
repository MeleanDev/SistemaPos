<?php

namespace App\Service\Empresa;

use App\Models\Cliente;

class ClienteClass
{
    public function lista()
    {
        return Cliente::select(
            'id',
            'cedula',
            'nombre',
            'apellido',
            'telefono',
            'correo',
            'direccion',
            'tipo_cliente',
            'estado'
        );
    }

    public function detalle($id)
    {
        return Cliente::findOrFail($id);
    }

    public function guardar(array $datos)
    {
        $clienteExistente = Cliente::where('cedula', $datos['cedula'])->first();

        if ($clienteExistente) {
            $datos['estado'] = true;
            $clienteExistente->update($datos);

            return $clienteExistente;
        }

        $datos['estado'] = true;

        return Cliente::create($datos);
    }

    public function actualizar(array $datos, $id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->update($datos);

        return $cliente;
    }

    public function eliminar($id)
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->estado = ! $cliente->estado;
        $cliente->save();

        return $cliente;
    }
}
