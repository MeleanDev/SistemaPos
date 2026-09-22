<?php

namespace App\Http\Requests\Finanzas\CuentaPorPagar;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class AbonarFacturaRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cuenta_id' => [
                'required',
                'integer',
                Rule::exists('cuentas_por_pagar', 'id')->where(fn ($query) => $query->where('empresa_id', $this->empresaId())),
            ],
            'metodo_pago_id' => [
                'required',
                'integer',
                Rule::exists('metodos_pago', 'id')->where(fn ($query) => $query->where('estado', true)),
            ],
            'moneda' => ['required', 'string', 'in:USD,VES'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'tasa_cambio' => ['required', 'numeric', 'min:0.0001'],
            'fecha_abono' => ['nullable', 'date'],
            'referencia' => ['nullable', 'string', 'max:100'],
            'observaciones' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cuenta_id.required' => 'La cuenta por pagar es obligatoria.',
            'cuenta_id.exists' => 'La cuenta por pagar seleccionada no existe o no pertenece a esta empresa.',
            'metodo_pago_id.required' => 'El método de pago es obligatorio.',
            'metodo_pago_id.exists' => 'El método de pago seleccionado no es válido o está inactivo.',
            'moneda.required' => 'La moneda del pago es obligatoria.',
            'moneda.in' => 'La moneda seleccionada no es válida (debe ser USD o VES).',
            'monto.required' => 'El monto a pagar/abonar es obligatorio.',
            'monto.numeric' => 'El monto debe ser un valor numérico.',
            'monto.min' => 'El monto a pagar debe ser mayor a 0.',
            'tasa_cambio.required' => 'La tasa de cambio es obligatoria.',
            'tasa_cambio.numeric' => 'La tasa de cambio debe ser un valor numérico.',
            'tasa_cambio.min' => 'La tasa de cambio debe ser mayor a 0.',
            'fecha_abono.date' => 'La fecha de pago no tiene un formato de fecha válido.',
            'referencia.max' => 'La referencia no debe exceder los 100 caracteres.',
            'observaciones.max' => 'Las observaciones no deben exceder los 255 caracteres.',
        ];
    }
}
