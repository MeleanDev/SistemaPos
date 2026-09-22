<?php

namespace App\Http\Requests\Configuracion;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class ActualizarTasasRequest extends BaseRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'monedas' => ['required', 'array', 'min:1'],
            'monedas.*.codigo' => ['required', 'string', 'max:10'],
            'monedas.*.tasa_cambio' => ['required', 'numeric', 'min:0.0001'],
            'monedas.*.estado' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'monedas.required' => 'Debe enviar las monedas a actualizar.',
            'monedas.*.codigo.required' => 'El código de la moneda es obligatorio.',
            'monedas.*.tasa_cambio.required' => 'La tasa de cambio es obligatoria.',
            'monedas.*.tasa_cambio.min' => 'La tasa de cambio debe ser mayor a 0.',
        ];
    }
}
