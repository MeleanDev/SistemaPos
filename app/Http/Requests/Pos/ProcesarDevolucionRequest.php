<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;
use App\Models\CajaTurno;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ProcesarDevolucionRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $empresaId = $this->empresaId();

        if (! $user || $empresaId <= 0) {
            return;
        }

        if (! $this->filled('caja_turno_id')) {
            $turnoPropio = $user->cajaTurnoActivo($empresaId);
            if ($turnoPropio) {
                $this->merge([
                    'caja_turno_id' => $turnoPropio->id,
                ]);
            } elseif ($user->hasAnyRole(['SuperAdmin', 'Admin'])) {
                $turnoEmpresa = CajaTurno::where('empresa_id', $empresaId)
                    ->where('estado', 'abierta')
                    ->orderBy('id', 'asc')
                    ->first();

                if ($turnoEmpresa) {
                    $this->merge([
                        'caja_turno_id' => $turnoEmpresa->id,
                    ]);
                }
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $empresaId = $this->empresaId();

        return [
            'venta_id' => [
                'required',
                'integer',
                Rule::exists('ventas', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)),
            ],
            'caja_turno_id' => [
                'required',
                'integer',
                Rule::exists('caja_turnos', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', 'abierta')),
            ],
            'motivo' => ['required', 'string', 'max:255'],
            'tipo_reembolso' => [
                'nullable',
                'string',
                'in:sin_reembolso,efectivo,transferencia,otro_metodo,metodo_original,credito,credito_cliente,credito_favor,nota_credito,saldo_cxc',
            ],
            'metodo_pago_id' => [
                'nullable',
                'integer',
                Rule::exists('metodos_pago', 'id')->where(fn ($q) => $q->where('estado', true)),
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.venta_detalle_id' => ['required', 'integer'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
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
            'venta_id.required' => 'La venta a devolver es obligatoria.',
            'venta_id.exists' => 'La venta especificada no pertenece a la empresa o no existe.',
            'caja_turno_id.required' => 'Debe existir un turno de caja abierto para procesar la devolución.',
            'caja_turno_id.exists' => 'El turno de caja seleccionado no existe, no está abierto o no pertenece a la empresa.',
            'motivo.required' => 'El motivo de la devolución es obligatorio.',
            'motivo.max' => 'El motivo no debe superar los 255 caracteres.',
            'metodo_pago_id.exists' => 'El método de pago de reembolso seleccionado no es válido o está inactivo.',
            'items.required' => 'Debe seleccionar al menos un producto a devolver.',
            'items.min' => 'Debe seleccionar al menos un producto a devolver.',
            'items.*.venta_detalle_id.required' => 'El identificador del renglón es requerido.',
            'items.*.cantidad.required' => 'La cantidad a devolver es requerida.',
            'items.*.cantidad.min' => 'La cantidad a devolver debe ser mayor a 0.',
        ];
    }
}
