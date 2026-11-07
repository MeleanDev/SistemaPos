<?php

namespace App\Http\Requests\Vendedor;

use App\Http\Requests\BaseRequest;
use App\Models\Empresa;
use Illuminate\Contracts\Validation\ValidationRule;

class LiquidarComisionRequest extends BaseRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || (! $user->hasRole('SuperAdmin') && ! $user->can('vendedores.liquidar'))) {
            return false;
        }

        $empresa = Empresa::find($this->empresaId());

        return (bool) ($empresa?->maneja_vendedores ?? false);
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('registrar_egreso_caja') && $this->has('pagado_por_caja')) {
            $this->merge([
                'registrar_egreso_caja' => $this->boolean('pagado_por_caja'),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'venta_ids' => ['nullable', 'array'],
            'venta_ids.*' => ['integer'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'registrar_egreso_caja' => ['nullable', 'boolean'],
            'caja_turno_id' => ['nullable', 'integer'],
            'metodo_pago_id' => ['nullable', 'integer', 'exists:metodos_pago,id'],
            'moneda' => ['nullable', 'string', 'in:USD,VES,BS,usd,ves,bs'],
            'observaciones' => ['nullable', 'string', 'max:500'],
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
            'venta_ids.array' => 'El formato de facturas seleccionadas no es válido.',
            'fecha_inicio.date' => 'La fecha de inicio debe tener un formato válido.',
            'fecha_fin.date' => 'La fecha final debe tener un formato válido.',
            'fecha_fin.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha de inicio.',
            'metodo_pago_id.exists' => 'El método de pago seleccionado no es válido.',
            'observaciones.max' => 'Las observaciones no pueden exceder los 500 caracteres.',
        ];
    }
}
