<?php

namespace App\Service\Empresa;

use App\Models\MetodoPago;
use Illuminate\Database\Eloquent\Builder;

class MetodoPagoClass
{
    /**
     * Listado de métodos de pago activos
     */
    public function lista(): Builder
    {
        return MetodoPago::select(
            'id',
            'nombre',
            'descripcion',
            'estado',
            'created_at'
        )->where('estado', true);
    }

    /**
     * Detalle de un método de pago por ID
     */
    public function detalle(int $id): MetodoPago
    {
        return MetodoPago::findOrFail($id);
    }

    /**
     * Guardar nuevo método de pago o reactivar existente
     */
    public function guardar(array $datos): MetodoPago
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

    /**
     * Actualizar método de pago existente
     */
    public function actualizar(array $datos, int $id): MetodoPago
    {
        $metodo = MetodoPago::findOrFail($id);
        $metodo->update($datos);

        return $metodo;
    }

    /**
     * Borrado lógico de un método de pago
     */
    public function eliminar(int $id): MetodoPago
    {
        $metodo = MetodoPago::findOrFail($id);
        $metodo->estado = false;
        $metodo->save();

        return $metodo;
    }
}
