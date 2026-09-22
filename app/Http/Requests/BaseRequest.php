<?php

namespace App\Http\Requests;

use App\Traits\HasEmpresaActiva;
use Illuminate\Foundation\Http\FormRequest;

abstract class BaseRequest extends FormRequest
{
    use HasEmpresaActiva;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Alias de conveniencia para obtener el ID de la empresa activa.
     */
    protected function empresaId(): int
    {
        return $this->obtenerEmpresaId();
    }
}
