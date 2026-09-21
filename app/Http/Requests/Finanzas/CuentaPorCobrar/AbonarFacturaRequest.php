<?php

namespace App\Http\Requests\Finanzas\CuentaPorCobrar;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AbonarFacturaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $empresaId = $this->user()?->empresaActiva()?->id ?? session('empresa_activa_id');

        return [
            'cuenta_id' => [
                'required',
                'integer',
                Rule::exists('cuentas_por_cobrar', 'id')->where(fn ($query) => $query->where('empresa_id', $empresaId)),
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
            'cuenta_id.required' => 'La cuenta por cobrar es obligatoria.',
            'cuenta_id.exists' => 'La cuenta por cobrar seleccionada no existe o no pertenece a esta empresa.',
            'metodo_pago_id.required' => 'El método de pago es obligatorio.',
            'metodo_pago_id.exists' => 'El método de pago seleccionado no es válido o está inactivo.',
            'moneda.required' => 'La moneda del abono es obligatoria.',
            'moneda.in' => 'La moneda seleccionada no es válida (debe ser USD o VES).',
            'monto.required' => 'El monto a abonar es obligatorio.',
            'monto.numeric' => 'El monto debe ser un valor numérico.',
            'monto.min' => 'El monto a abonar debe ser mayor a 0.',
            'tasa_cambio.required' => 'La tasa de cambio es obligatoria.',
            'tasa_cambio.numeric' => 'La tasa de cambio debe ser un valor numérico.',
            'tasa_cambio.min' => 'La tasa de cambio debe ser mayor a 0.',
            'fecha_abono.date' => 'La fecha de abono no tiene un formato de fecha válido.',
            'referencia.max' => 'La referencia no debe exceder los 100 caracteres.',
            'observaciones.max' => 'Las observaciones no deben exceder los 255 caracteres.',
        ];
    }
}
