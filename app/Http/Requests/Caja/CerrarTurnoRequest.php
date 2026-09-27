<?php

namespace App\Http\Requests\Caja;

use App\Http\Requests\BaseRequest;

class CerrarTurnoRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'monto_cierre_usd' => ['required', 'numeric', 'min:0'],
            'monto_cierre_bs' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'monto_cierre_usd.required' => 'El arqueo físico en USD es obligatorio.',
            'monto_cierre_usd.numeric' => 'El arqueo físico en USD debe ser un número.',
            'monto_cierre_usd.min' => 'El arqueo en USD no puede ser negativo.',
            'monto_cierre_bs.required' => 'El arqueo físico en Bs. es obligatorio.',
            'monto_cierre_bs.numeric' => 'El arqueo físico en Bs. debe ser un número.',
            'monto_cierre_bs.min' => 'El arqueo en Bs. no puede ser negativo.',
            'observaciones.max' => 'Las observaciones no deben superar los 500 caracteres.',
        ];
    }
}
