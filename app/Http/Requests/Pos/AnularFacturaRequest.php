<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;
use App\Models\CajaTurno;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AnularFacturaRequest extends BaseRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('motivo') && $this->filled('motivo_anulacion')) {
            $this->merge([
                'motivo' => $this->input('motivo_anulacion'),
            ]);
        }

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $empresaId = $this->empresaId();

        return [
            'motivo' => ['required', 'string', 'min:5', 'max:500'],
            'motivo_anulacion' => ['nullable', 'string', 'min:5', 'max:500'],
            'caja_turno_id' => [
                'required',
                'integer',
                Rule::exists('caja_turnos', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', 'abierta')),
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $user = $this->user();
                $empresaId = $this->empresaId();
                $cajaTurnoId = (int) $this->input('caja_turno_id');

                if (! $user || $empresaId <= 0 || $cajaTurnoId <= 0) {
                    return;
                }

                $esAdmin = $user->hasAnyRole(['SuperAdmin', 'Admin']);
                if (! $esAdmin) {
                    $turnoPropio = $user->cajaTurnoActivo($empresaId);
                    if (! $turnoPropio || (int) $turnoPropio->id !== $cajaTurnoId) {
                        $validator->errors()->add('caja_turno_id', 'No puedes anular facturas utilizando el turno de otro cajero.');
                    }
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo de anulación es obligatorio.',
            'motivo.min' => 'El motivo de anulación debe tener al menos 5 caracteres.',
            'motivo.max' => 'El motivo de anulación no debe superar los 500 caracteres.',
            'caja_turno_id.required' => 'Debe existir un turno de caja abierto para anular la factura.',
            'caja_turno_id.exists' => 'El turno de caja seleccionado no existe, no está abierto o no pertenece a la empresa.',
        ];
    }
}
