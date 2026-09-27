<?php

namespace App\Service\Administradores;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class EmpresaClass
{
    /**
     * Listado de empresas activas
     */
    public function lista(): Builder
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
            'maneja_motos',
            'estado',
            'created_at'
        )->where('estado', true);
    }

    /**
     * Detalle de una empresa por ID
     */
    public function detalle(int $id): Empresa
    {
        return Empresa::findOrFail($id);
    }

    /**
     * Guardar nueva empresa o reactivar existente
     */
    public function guardar(array $datos): Empresa
    {
        if (isset($datos['logo']) && $datos['logo'] instanceof UploadedFile) {
            $datos['logo'] = $datos['logo']->store('empresas', 'public');
        }

        $datos['maneja_motos'] = ! empty($datos['maneja_motos']);

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

    /**
     * Actualizar empresa existente
     */
    public function actualizar(array $datos, int $id): Empresa
    {
        $empresa = Empresa::findOrFail($id);

        if (isset($datos['logo']) && $datos['logo'] instanceof UploadedFile) {
            if (! empty($empresa->logo) && Storage::disk('public')->exists($empresa->logo)) {
                Storage::disk('public')->delete($empresa->logo);
            }
            $datos['logo'] = $datos['logo']->store('empresas', 'public');
        } else {
            unset($datos['logo']);
        }

        $datos['maneja_motos'] = ! empty($datos['maneja_motos']);

        $empresa->update($datos);

        return $empresa;
    }

    /**
     * Borrado lógico de una empresa
     */
    public function eliminar(int $id): Empresa
    {
        $empresa = Empresa::findOrFail($id);
        $empresa->estado = false;
        $empresa->save();

        return $empresa;
    }
}
