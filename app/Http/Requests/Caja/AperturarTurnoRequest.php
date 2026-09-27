<?php

namespace App\Http\Requests\Caja;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Rule;

class AperturarTurnoRequest extends BaseRequest
{
    public function rules(): array
    {
        $empresaId = $this->empresaId();

        return [
            'caja_id' => [
                'required',
                'integer',
                Rule::exists('cajas', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)->where('estado', true)),
            ],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'monto_apertura_usd' => ['required', 'numeric', 'min:0'],
            'monto_apertura_bs' => ['required', 'numeric', 'min:0'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'caja_id.required' => 'Debes seleccionar una caja para aperturar el turno.',
            'caja_id.exists' => 'La caja seleccionada no es válida o está inactiva.',
            'monto_apertura_usd.required' => 'El monto inicial en USD es obligatorio (ingresa 0 si no hay fondo).',
            'monto_apertura_usd.numeric' => 'El monto inicial en USD debe ser numérico.',
            'monto_apertura_usd.min' => 'El monto inicial en USD no puede ser negativo.',
            'monto_apertura_bs.required' => 'El monto inicial en Bs. es obligatorio (ingresa 0 si no hay fondo).',
            'monto_apertura_bs.numeric' => 'El monto inicial en Bs. debe ser numérico.',
            'monto_apertura_bs.min' => 'El monto inicial en Bs. no puede ser negativo.',
            'observaciones.max' => 'Las observaciones no deben superar los 500 caracteres.',
        ];
    }
}
