<?php

namespace App\Http\Requests\Caja;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class MovimientoCajaRequest extends BaseRequest
{
    public function rules(): array
    {
        $empresaId = $this->empresaId();

        return [
            'caja_turno_id' => [
                'required',
                'integer',
                Rule::exists('caja_turnos', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)->where('estado', 'abierta')),
            ],
            'tipo' => ['required', 'string', Rule::in(['ingreso', 'egreso'])],
            'monto_usd' => ['required', 'numeric', 'min:0.01'],
            'monto_bs' => ['required', 'numeric', 'min:0'],
            'concepto' => ['required', 'string', 'max:255'],
            'comprobante_referencia' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'caja_turno_id.required' => 'El turno de caja es obligatorio.',
            'caja_turno_id.exists' => 'El turno de caja no es válido o ya se encuentra cerrado.',
            'tipo.required' => 'El tipo de movimiento es obligatorio.',
            'tipo.in' => 'El tipo de movimiento debe ser ingreso o egreso.',
            'monto_usd.required' => 'El monto en USD es obligatorio.',
            'monto_usd.min' => 'El monto en USD debe ser mayor a 0.',
            'monto_bs.required' => 'El monto en Bs es obligatorio.',
            'monto_bs.min' => 'El monto en Bs debe ser mayor o igual a 0.',
            'concepto.required' => 'El concepto o motivo del movimiento es obligatorio.',
            'concepto.max' => 'El concepto no puede superar los 255 caracteres.',
        ];
    }
}
