<?php

namespace App\Http\Requests\Caja;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'monto_usd' => ['required', 'numeric', 'min:0'],
            'monto_bs' => ['required', 'numeric', 'min:0'],
            'concepto' => ['required', 'string', 'max:255'],
            'comprobante_referencia' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $montoUsd = (float) $this->input('monto_usd', 0);
            $montoBs = (float) $this->input('monto_bs', 0);

            if ($montoUsd <= 0 && $montoBs <= 0) {
                $validator->errors()->add('monto_usd', 'Debes ingresar un monto mayor a cero en USD ($) o en Bolívares (Bs.).');
            }
        });
    }

    public function messages(): array
    {
        return [
            'caja_turno_id.required' => 'El turno de caja es obligatorio.',
            'caja_turno_id.exists' => 'El turno de caja no es válido o ya se encuentra cerrado.',
            'tipo.required' => 'El tipo de movimiento es obligatorio.',
            'tipo.in' => 'El tipo de movimiento debe ser ingreso o egreso.',
            'monto_usd.required' => 'El monto en USD es obligatorio (ingresa 0 si es solo en Bs.).',
            'monto_usd.numeric' => 'El monto en USD debe ser numérico.',
            'monto_usd.min' => 'El monto en USD no puede ser negativo.',
            'monto_bs.required' => 'El monto en Bs. es obligatorio (ingresa 0 si es solo en USD).',
            'monto_bs.numeric' => 'El monto en Bs. debe ser numérico.',
            'monto_bs.min' => 'El monto en Bs. no puede ser negativo.',
            'concepto.required' => 'El concepto o motivo del movimiento es obligatorio.',
            'concepto.max' => 'El concepto no puede superar los 255 caracteres.',
            'comprobante_referencia.max' => 'La referencia no puede superar los 100 caracteres.',
            'observaciones.max' => 'Las observaciones no pueden superar los 500 caracteres.',
        ];
    }
}
