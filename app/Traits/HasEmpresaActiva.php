<?php

namespace App\Traits;

use App\Models\Empresa;
use Illuminate\Support\Facades\Auth;

trait HasEmpresaActiva
{
    /**
     * Obtiene el ID de la empresa activa para el contexto actual.
     */
    protected function obtenerEmpresaId(): int
    {
        $user = method_exists($this, 'user') ? $this->user() : Auth::user();
        $empresa = $user?->empresaActiva();
        $empresaId = $empresa?->id ?? session('empresa_activa_id');

        if (! $empresaId) {
            abort(403, 'No tienes una empresa activa asignada o seleccionada.');
        }

        return (int) $empresaId;
    }

    /**
     * Obtiene la instancia del modelo de la empresa activa.
     */
    protected function obtenerEmpresaActiva(): ?Empresa
    {
        $user = method_exists($this, 'user') ? $this->user() : Auth::user();

        return $user?->empresaActiva();
    }
}
