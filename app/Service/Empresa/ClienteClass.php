<?php

namespace App\Service\Empresa;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Builder;

class ClienteClass
{
    /**
     * Listado de clientes activos (Query para DataTables)
     */
    public function lista(): Builder
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
        )->where('estado', true);
    }

    /**
     * Detalle de un cliente por ID
     */
    public function detalle(int $id): Cliente
    {
        return Cliente::findOrFail($id);
    }

    /**
     * Guardar nuevo cliente o reactivar existente
     */
    public function guardar(array $datos): Cliente
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

    /**
     * Actualizar cliente existente
     */
    public function actualizar(array $datos, int $id): Cliente
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->update($datos);

        return $cliente;
    }

    /**
     * Borrado lógico de un cliente
     */
    public function eliminar(int $id): Cliente
    {
        $cliente = Cliente::findOrFail($id);
        $cliente->estado = false;
        $cliente->save();

        return $cliente;
    }
}
