<?php

namespace App\Http\Requests\Pos;

use App\Http\Requests\BaseRequest;
use App\Models\CajaTurno;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarVentaRequest extends BaseRequest
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

        $esAdmin = $user->hasAnyRole(['SuperAdmin', 'Admin']);

        if (! $esAdmin && ! $this->filled('caja_turno_id')) {
            $turnoPropio = $user->cajaTurnoActivo($empresaId);
            if ($turnoPropio) {
                $this->merge([
                    'caja_turno_id' => $turnoPropio->id,
                ]);
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
            'cliente_id' => [
                'required',
                'integer',
                Rule::exists('clientes', 'id')->where(fn ($q) => $q->where('estado', true)),
            ],
            'almacen_id' => [
                'required',
                'integer',
                Rule::exists('almacenes', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
            ],
            'vendedor_id' => [
                'nullable',
                'integer',
                Rule::exists('vendedores', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
            ],
            'venta_en_espera_id' => ['nullable', 'integer'],
            'caja_turno_id' => [
                'required',
                'integer',
                Rule::exists('caja_turnos', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', 'abierta')),
            ],
            'tipo_venta' => ['required', 'string', 'in:detal,mayor,mayorista'],
            'condicion_pago' => ['nullable', 'string', 'in:contado,credito'],
            'dias_credito' => ['nullable', 'integer', 'min:0'],
            'tasa_cambio' => ['required', 'numeric', 'min:0.0001'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', 'integer'],
            'items.*.tipo_item' => ['nullable', 'string', 'in:producto,moto,servicio'],
            'items.*.cantidad' => ['required', 'numeric', 'min:0.0001'],
            'items.*.precio_unitario_usd' => ['required', 'numeric', 'min:0.0001'],
            'items.*.descuento_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.descuento_usd' => ['nullable', 'numeric', 'min:0'],
            'items.*.almacen_id' => ['nullable', 'integer'],
            'items.*.variante_texto' => ['nullable', 'string', 'max:255'],
            'items.*.producto_serial_id' => ['nullable', 'integer'],
            'pagos' => ['nullable', 'array'],
            'pagos.*.metodo_pago_id' => ['required_with:pagos', 'integer'],
            'pagos.*.monto' => ['required_with:pagos', 'numeric', 'min:0.0001'],
            'pagos.*.moneda' => ['nullable', 'string', 'max:10'],
            'pagos.*.tasa_cambio' => ['nullable', 'numeric', 'min:0.0001'],
            'pagos.*.referencia' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            $empresaId = $this->empresaId();

            if (! $user || $empresaId <= 0) {
                return;
            }

            $esAdmin = $user->hasAnyRole(['SuperAdmin', 'Admin']);

            if (! $esAdmin && $this->filled('caja_turno_id')) {
                $turnoPropio = $user->cajaTurnoActivo($empresaId);
                if (! $turnoPropio) {
                    $validator->errors()->add('caja_turno_id', 'No tienes una caja registradora aperturada asignada para procesar cobros.');
                } elseif ((int) $this->input('caja_turno_id') !== (int) $turnoPropio->id) {
                    $validator->errors()->add('caja_turno_id', 'Solo puedes facturar utilizando tu propio turno de caja abierto.');
                }
            }
        });
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $empresaId = $this->empresaId();
        $user = $this->user();
        $esAdmin = $user ? $user->hasAnyRole(['SuperAdmin', 'Admin']) : false;

        $mensajeCajaTurnoRequired = 'No tienes una caja registradora aperturada asignada para procesar cobros.';
        if ($esAdmin) {
            $hayAbiertas = $empresaId > 0 && CajaTurno::where('empresa_id', $empresaId)->where('estado', 'abierta')->exists();
            $mensajeCajaTurnoRequired = $hayAbiertas
                ? 'Debes seleccionar una caja con turno abierto para procesar la venta.'
                : 'No hay cajas abiertas para procesar la venta.';
        }

        return [
            'cliente_id.required' => 'El cliente es obligatorio.',
            'cliente_id.exists' => 'El cliente seleccionado no es válido o está inactivo.',
            'almacen_id.required' => 'El almacén de despacho es obligatorio.',
            'almacen_id.exists' => 'El almacén seleccionado no pertenece a la empresa o no está disponible.',
            'caja_turno_id.required' => $mensajeCajaTurnoRequired,
            'caja_turno_id.exists' => 'El turno de caja seleccionado no pertenece a la empresa o no se encuentra abierto.',
            'tipo_venta.required' => 'El tipo de venta es obligatorio.',
            'tasa_cambio.required' => 'La tasa de cambio es obligatoria.',
            'tasa_cambio.min' => 'La tasa de cambio debe ser mayor a 0.',
            'items.required' => 'Debe incluir al menos un producto o servicio en la venta.',
            'items.min' => 'Debe incluir al menos un producto o servicio en la venta.',
            'items.*.producto_id.required' => 'El identificador del ítem es requerido.',
            'items.*.cantidad.required' => 'La cantidad del ítem es requerida.',
            'items.*.cantidad.min' => 'La cantidad debe ser mayor a 0.',
            'items.*.precio_unitario_usd.required' => 'El precio del ítem es requerido.',
            'items.*.precio_unitario_usd.min' => 'El precio unitario debe ser mayor a 0.',
        ];
    }
}
