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
            'almacen_id' => [
                'nullable',
                'integer',
                Rule::exists('almacenes', 'id')->where(fn ($q) => $q->where('empresa_id', $empresaId)->where('estado', true)),
            ],
            'color' => ['nullable', 'string', 'max:50'],
            'placa' => ['nullable', 'string', 'max:20'],
            'precio_costo_usd' => ['nullable', 'numeric', 'min:0'],
            'precio_costo_bs' => ['nullable', 'numeric', 'min:0'],
            'margen_detal' => ['nullable', 'numeric', 'min:0'],
            'precio_detal_usd' => ['nullable', 'numeric', 'min:0'],
            'precio_detal_bs' => ['nullable', 'numeric', 'min:0'],
            'margen_mayorista' => ['nullable', 'numeric', 'min:0'],
            'precio_mayorista_usd' => ['nullable', 'numeric', 'min:0'],
            'precio_mayorista_bs' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable', 'string', 'in:disponible,vendida,reservada,mantenimiento,garantia,anulada'],
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
            'almacen_id.exists' => 'El almacén seleccionado no es válido para esta empresa.',
            'estado.in' => 'El estado especificado no es válido.',
        ];
    }
}
