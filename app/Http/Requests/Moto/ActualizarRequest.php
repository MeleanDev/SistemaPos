<?php

namespace App\Http\Requests\Moto;

use App\Http\Requests\BaseRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ActualizarRequest extends BaseRequest
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
        $empresaId = $this->empresaId();

        return [
            'marca' => ['required', 'string', 'max:100'],
            'modelo' => ['required', 'string', 'max:100'],
            'referencia' => ['nullable', 'string', 'max:50'],
            'anio' => ['required', 'integer', 'min:1900', 'max:2099'],
            'color' => ['nullable', 'string', 'max:50'],
            'cilindrada' => ['nullable', 'string', 'max:50'],
            'numero_niv' => [
                'required',
                'string',
                'max:50',
                Rule::unique('motos', 'numero_niv')
                    ->where(fn ($q) => $q->where('empresa_id', $empresaId))
                    ->ignore($this->route('id')),
            ],
            'numero_chasis' => ['required', 'string', 'max:50'],
            'numero_motor' => ['required', 'string', 'max:50'],
            'certificado_origen' => ['nullable', 'string', 'max:50'],
            'placa' => ['nullable', 'string', 'max:20'],
            'almacen_id' => [
                'required',
                'integer',
                Rule::exists('almacenes', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
            ],
            'precio_costo_usd' => ['nullable', 'numeric', 'min:0'],
            'precio_costo_bs' => ['nullable', 'numeric', 'min:0'],
            'margen_detal' => ['nullable', 'numeric', 'min:0'],
            'precio_detal_usd' => ['required', 'numeric', 'min:0'],
            'precio_detal_bs' => ['nullable', 'numeric', 'min:0'],
            'margen_mayorista' => ['nullable', 'numeric', 'min:0'],
            'precio_mayorista_usd' => ['required', 'numeric', 'min:0'],
            'precio_mayorista_bs' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['required', 'string', 'in:disponible,vendida,reservada,en_mantenimiento,mantenimiento,garantia,anulada'],
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
            'marca.required' => 'La marca del vehículo es requerida.',
            'modelo.required' => 'El modelo del vehículo es requerido.',
            'anio.required' => 'El año de fabricación es requerido.',
            'numero_niv.required' => 'El N.I.V. (VIN) es requerido.',
            'numero_niv.unique' => 'Ya existe otra moto registrada con este número de N.I.V.',
            'numero_chasis.required' => 'El número de chasis es requerido.',
            'numero_motor.required' => 'El número de motor es requerido.',
            'almacen_id.required' => 'El almacén de ubicación es requerido.',
            'almacen_id.exists' => 'El almacén seleccionado no es válido para esta empresa.',
            'precio_detal_usd.required' => 'El precio de venta al detal es requerido.',
            'precio_mayorista_usd.required' => 'El precio mayorista es requerido.',
            'estado.required' => 'El estado del vehículo es requerido.',
            'estado.in' => 'El estado especificado no es válido.',
        ];
    }
}
