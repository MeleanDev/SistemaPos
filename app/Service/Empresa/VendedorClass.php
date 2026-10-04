<?php

namespace App\Service\Empresa;

use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VendedorClass
{
    /**
     * Obtener usuarios del sistema elegibles para vincular a vendedores
     */
    public function usuariosDisponibles(int $empresaId): Collection
    {
        return User::where('estado', true)
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'SuperAdmin'))
            ->whereHas('empresas', function ($q) use ($empresaId) {
                $q->where('empresas.id', $empresaId)
                    ->where('empresa_user.estado', true);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'nombre', 'apellido', 'email']);
    }

    /**
     * Listado de vendedores para DataTables
     */
    public function lista(int $empresaId): Builder
    {
        return Vendedor::with('user:id,name,nombre,apellido,email')
            ->select(
                'id',
                'empresa_id',
                'user_id',
                'tipo_documento',
                'documento',
                'nombre',
                'telefono',
                'correo',
                'comision_porcentaje',
                'estado'
            )->where('empresa_id', $empresaId);
    }

    /**
     * Obtener listado de vendedores activos para selectores
     */
    public function activos(int $empresaId): Collection
    {
        return Vendedor::where('empresa_id', $empresaId)
            ->where('estado', true)
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Detalle de un vendedor
     */
    public function detalle(int $id, int $empresaId): Vendedor
    {
        return Vendedor::where('empresa_id', $empresaId)->findOrFail($id);
    }

    /**
     * Guardar nuevo vendedor
     */
    public function guardar(array $datos, int $empresaId): Vendedor
    {
        $datos['empresa_id'] = $empresaId;
        $datos['estado'] = true;
        if (isset($datos['tipo_documento'])) {
            $datos['tipo_documento'] = strtoupper(str_replace('-', '', (string) $datos['tipo_documento']));
        }

        return Vendedor::create($datos);
    }

    /**
     * Actualizar vendedor
     */
    public function actualizar(array $datos, int $id, int $empresaId): Vendedor
    {
        $vendedor = $this->detalle($id, $empresaId);
        if (isset($datos['tipo_documento'])) {
            $datos['tipo_documento'] = strtoupper(str_replace('-', '', (string) $datos['tipo_documento']));
        }
        $vendedor->update($datos);

        return $vendedor;
    }

    /**
     * Alternar estado (activo / inactivo)
     */
    public function eliminar(int $id, int $empresaId): Vendedor
    {
        $vendedor = $this->detalle($id, $empresaId);
        $vendedor->estado = ! $vendedor->estado;
        $vendedor->save();

        return $vendedor;
    }
}
