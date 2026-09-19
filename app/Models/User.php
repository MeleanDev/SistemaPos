<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'nombre', 'apellido', 'email', 'password', 'estado'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'estado' => 'boolean',
        ];
    }

    /**
     * Nombre completo del usuario
     */
    public function getNombreCompletoAttribute(): string
    {
        $nombreCompleto = trim(($this->nombre ?? '').' '.($this->apellido ?? ''));

        return ! empty($nombreCompleto) ? $nombreCompleto : $this->name;
    }

    /**
     * Empresas a las que el usuario tiene acceso
     */
    public function empresas(): BelongsToMany
    {
        return $this->belongsToMany(Empresa::class, 'empresa_user')
            ->withPivot('es_predeterminada', 'estado')
            ->withTimestamps();
    }

    /**
     * Obtiene la colección de empresas a las que tiene permiso
     */
    public function obtenerEmpresasPermitidas(): Collection
    {
        if ($this->hasRole('SuperAdmin')) {
            return Empresa::where('estado', true)->orderBy('nombre')->get();
        }

        return $this->empresas()
            ->wherePivot('estado', true)
            ->where('empresas.estado', true)
            ->orderBy('nombre')
            ->get();
    }

    /**
     * Obtiene la empresa activa actual del contexto de sesión
     */
    public function empresaActiva(): ?Empresa
    {
        $empresasPermitidas = $this->obtenerEmpresasPermitidas();

        if ($empresasPermitidas->isEmpty()) {
            return null;
        }

        $empresaActivaId = session('empresa_activa_id');

        if ($empresaActivaId) {
            $activa = $empresasPermitidas->firstWhere('id', $empresaActivaId);
            if ($activa) {
                return $activa;
            }
        }

        // Si no hay en sesión o no es válida, tomar la primera permitida y guardarla en sesión
        $primera = $empresasPermitidas->first();
        session(['empresa_activa_id' => $primera->id]);

        return $primera;
    }
}
